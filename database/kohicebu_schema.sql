--
-- PostgreSQL database dump
--

\restrict M2NBzrkogTNDo04OmlcSPrDccaBqfrrjGr2twNRj4ZcSKEwdNs2lUnxQF7UGEcv

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: apply_inventory_adjustment(integer, integer, numeric, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.apply_inventory_adjustment(p_ingredient_id integer, p_user_id integer, p_actual_quantity numeric, p_reason text) RETURNS TABLE(transaction_id integer, ingredient_id integer, quantity_before numeric, quantity_change numeric, quantity_after numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_current_quantity NUMERIC(12,3);
    v_quantity_change NUMERIC(12,3);
    v_transaction_id INTEGER;
BEGIN

    -- Actual quantity cannot be negative
    IF p_actual_quantity < 0 THEN
        RAISE EXCEPTION 'Actual inventory quantity cannot be negative.';
    END IF;

    -- Reason is required
    IF p_reason IS NULL OR trim(p_reason) = '' THEN
        RAISE EXCEPTION 'Adjustment reason is required.';
    END IF;

    -- Lock the ingredient row
    SELECT i.current_quantity
    INTO v_current_quantity
    FROM ingredients AS i
    WHERE i.ingredient_id = p_ingredient_id
    FOR UPDATE;

    -- Ingredient must exist
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ingredient ID % does not exist.',
            p_ingredient_id;
    END IF;

    -- Calculate difference
    v_quantity_change :=
        p_actual_quantity - v_current_quantity;

    -- Nothing to adjust
    IF v_quantity_change = 0 THEN
        RAISE EXCEPTION
            'Actual quantity is already equal to current inventory.';
    END IF;

    -- Update current stock
    UPDATE ingredients AS i
    SET
        current_quantity = p_actual_quantity,
        updated_at = CURRENT_TIMESTAMP
    WHERE i.ingredient_id = p_ingredient_id;

    -- Record adjustment
    INSERT INTO inventory_transactions (
        ingredient_id,
        user_id,
        transaction_type,
        quantity_change,
        quantity_before,
        quantity_after,
        order_id,
        reason
    )
    VALUES (
        p_ingredient_id,
        p_user_id,
        'ADJUSTMENT',
        v_quantity_change,
        v_current_quantity,
        p_actual_quantity,
        NULL,
        p_reason
    )
    RETURNING inventory_transactions.transaction_id
    INTO v_transaction_id;

    RETURN QUERY
    SELECT
        v_transaction_id,
        p_ingredient_id,
        v_current_quantity,
        v_quantity_change,
        p_actual_quantity;

END;
$$;


--
-- Name: apply_inventory_transaction(integer, integer, character varying, numeric, integer, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.apply_inventory_transaction(p_ingredient_id integer, p_user_id integer, p_transaction_type character varying, p_quantity_change numeric, p_order_id integer DEFAULT NULL::integer, p_reason text DEFAULT NULL::text) RETURNS TABLE(transaction_id integer, ingredient_id integer, quantity_before numeric, quantity_change numeric, quantity_after numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_current_quantity NUMERIC(12,3);
    v_quantity_after NUMERIC(12,3);
    v_transaction_id INTEGER;
BEGIN

    -- Validate transaction type
    IF p_transaction_type NOT IN (
        'STOCK_IN',
        'STOCK_OUT',
        'ADJUSTMENT',
        'WASTE',
        'ORDER_DEDUCTION',
        'REVERSAL'
    ) THEN
        RAISE EXCEPTION 'Invalid inventory transaction type: %',
            p_transaction_type;
    END IF;

    -- Quantity change cannot be zero
    IF p_quantity_change = 0 THEN
        RAISE EXCEPTION 'Inventory quantity change cannot be zero.';
    END IF;

    -- Lock the ingredient row
    SELECT i.current_quantity
    INTO v_current_quantity
    FROM ingredients AS i
    WHERE i.ingredient_id = p_ingredient_id
    FOR UPDATE;

    -- Ingredient must exist
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ingredient ID % does not exist.',
            p_ingredient_id;
    END IF;

    -- Calculate new quantity
    v_quantity_after :=
        v_current_quantity + p_quantity_change;

    -- Never allow negative inventory
    IF v_quantity_after < 0 THEN
        RAISE EXCEPTION
            'Insufficient inventory for ingredient %. Current: %, Change: %',
            p_ingredient_id,
            v_current_quantity,
            p_quantity_change;
    END IF;

    -- Update ingredient stock
    UPDATE ingredients AS i
    SET
        current_quantity = v_quantity_after,
        updated_at = CURRENT_TIMESTAMP
    WHERE i.ingredient_id = p_ingredient_id;

    -- Create audit transaction
    INSERT INTO inventory_transactions (
        ingredient_id,
        user_id,
        transaction_type,
        quantity_change,
        quantity_before,
        quantity_after,
        order_id,
        reason
    )
    VALUES (
        p_ingredient_id,
        p_user_id,
        p_transaction_type,
        p_quantity_change,
        v_current_quantity,
        v_quantity_after,
        p_order_id,
        p_reason
    )
    RETURNING inventory_transactions.transaction_id
    INTO v_transaction_id;

    -- Return the result
    RETURN QUERY
    SELECT
        v_transaction_id,
        p_ingredient_id,
        v_current_quantity,
        p_quantity_change,
        v_quantity_after;

END;
$$;


--
-- Name: deduct_order_inventory(integer, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.deduct_order_inventory(p_order_id integer, p_user_id integer DEFAULT NULL::integer) RETURNS TABLE(ingredient_id integer, ingredient_name character varying, quantity_deducted numeric, quantity_before numeric, quantity_after numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_inventory_deducted BOOLEAN;
    v_order_exists BOOLEAN;
    v_product_has_recipe BOOLEAN;

    v_order_item RECORD;
    v_recipe RECORD;

    v_required_quantity NUMERIC(12,3);
    v_current_quantity NUMERIC(12,3);
BEGIN

    /*
     * -----------------------------------------------------
     * 1. Lock the order
     * -----------------------------------------------------
     *
     * This prevents two requests from deducting
     * inventory from the same order simultaneously.
     */
    SELECT
        o.inventory_deducted
    INTO
        v_inventory_deducted
    FROM orders AS o
    WHERE o.order_id = p_order_id
    FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION
            'Order ID % does not exist.',
            p_order_id;
    END IF;


    /*
     * -----------------------------------------------------
     * 2. Prevent duplicate deduction
     * -----------------------------------------------------
     */
    IF v_inventory_deducted = TRUE THEN

        RAISE EXCEPTION
            'Inventory has already been deducted for order %.',
            p_order_id;

    END IF;


    /*
     * -----------------------------------------------------
     * 3. Process every item in the order
     * -----------------------------------------------------
     */
    FOR v_order_item IN

        SELECT
            oi.product_id,
            oi.quantity,
            p.product_name

        FROM order_items AS oi

        INNER JOIN products AS p
            ON p.product_id = oi.product_id

        WHERE oi.order_id = p_order_id

        ORDER BY oi.product_id

    LOOP

        /*
         * -------------------------------------------------
         * Check whether the product has a recipe.
         * -------------------------------------------------
         */
        SELECT EXISTS (
            SELECT 1
            FROM product_recipes AS pr
            WHERE pr.product_id =
                v_order_item.product_id
        )
        INTO v_product_has_recipe;


        IF v_product_has_recipe = FALSE THEN

            RAISE EXCEPTION
                'Product "%" (ID %) does not have an inventory recipe configured.',
                v_order_item.product_name,
                v_order_item.product_id;

        END IF;


        /*
         * -------------------------------------------------
         * Process every ingredient in the recipe.
         * -------------------------------------------------
         */
        FOR v_recipe IN

            SELECT
                pr.ingredient_id,
                pr.quantity_required,
                i.ingredient_name

            FROM product_recipes AS pr

            INNER JOIN ingredients AS i
                ON i.ingredient_id =
                    pr.ingredient_id

            WHERE pr.product_id =
                v_order_item.product_id

            ORDER BY pr.ingredient_id

        LOOP

            /*
             * Calculate total ingredient requirement.
             *
             * Example:
             *
             * Recipe = 18g
             * Order quantity = 2
             *
             * 18 × 2 = 36g
             */
            v_required_quantity :=
                v_recipe.quantity_required *
                v_order_item.quantity;


            /*
             * Lock the ingredient row.
             */
            SELECT
                i.current_quantity

            INTO
                v_current_quantity

            FROM ingredients AS i

            WHERE i.ingredient_id =
                v_recipe.ingredient_id

            FOR UPDATE;


            IF NOT FOUND THEN

                RAISE EXCEPTION
                    'Ingredient ID % does not exist.',
                    v_recipe.ingredient_id;

            END IF;


            /*
             * Check inventory availability.
             */
            IF
                v_current_quantity <
                v_required_quantity
            THEN

                RAISE EXCEPTION
                    'Insufficient inventory for ingredient "%". Required: %, Available: %.',
                    v_recipe.ingredient_name,
                    v_required_quantity,
                    v_current_quantity;

            END IF;


            /*
             * -------------------------------------------------
             * Deduct inventory using the existing safe
             * inventory function.
             *
             * Negative quantity = inventory deduction.
             * -------------------------------------------------
             */
            PERFORM apply_inventory_transaction(
                v_recipe.ingredient_id,
                p_user_id,
                'ORDER_DEDUCTION',
                -v_required_quantity,
                p_order_id,
                'Automatic inventory deduction for Order #' ||
                    p_order_id
            );


            /*
             * Return the deduction information.
             */
            RETURN QUERY
            SELECT
                v_recipe.ingredient_id,
                v_recipe.ingredient_name,
                v_required_quantity,
                v_current_quantity,
                v_current_quantity -
                    v_required_quantity;

        END LOOP;

    END LOOP;


    /*
     * -----------------------------------------------------
     * 4. Mark the order as inventory-deducted.
     * -----------------------------------------------------
     */
    UPDATE orders AS o
    SET
        inventory_deducted = TRUE
    WHERE o.order_id = p_order_id;


END;
$$;


--
-- Name: deduct_product_recipe_inventory(integer, integer, integer, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.deduct_product_recipe_inventory(p_product_id integer, p_product_quantity integer, p_user_id integer, p_order_id integer) RETURNS TABLE(transaction_id integer, ingredient_id integer, ingredient_name character varying, quantity_before numeric, quantity_required numeric, quantity_after numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_recipe_count INTEGER;

    v_ingredient_id INTEGER;
    v_ingredient_name VARCHAR(100);

    v_current_quantity NUMERIC(12,3);
    v_required_quantity NUMERIC(12,3);

    v_transaction_id INTEGER;
    v_quantity_before NUMERIC(12,3);
    v_quantity_after NUMERIC(12,3);
BEGIN

    /*
     * ---------------------------------------------------------
     * VALIDATION
     * ---------------------------------------------------------
     */

    IF p_product_id <= 0 THEN
        RAISE EXCEPTION
            'Invalid product ID.';
    END IF;

    IF p_product_quantity <= 0 THEN
        RAISE EXCEPTION
            'Product quantity must be greater than zero.';
    END IF;

    IF p_user_id <= 0 THEN
        RAISE EXCEPTION
            'Invalid user ID.';
    END IF;

    IF p_order_id <= 0 THEN
        RAISE EXCEPTION
            'Invalid order ID.';
    END IF;


    /*
     * ---------------------------------------------------------
     * VERIFY RECIPE EXISTS
     * ---------------------------------------------------------
     */

    SELECT COUNT(*)
    INTO v_recipe_count
    FROM product_recipes AS pr
    WHERE pr.product_id = p_product_id;

    IF v_recipe_count = 0 THEN
        RAISE EXCEPTION
            'Product ID % has no configured recipe.',
            p_product_id;
    END IF;


    /*
     * ---------------------------------------------------------
     * FIRST PASS
     *
     * Lock every ingredient used by the recipe and verify
     * that the complete order quantity can be produced.
     *
     * NOTHING IS DEDUCTED HERE.
     * ---------------------------------------------------------
     */

    FOR
        v_ingredient_id,
        v_current_quantity,
        v_required_quantity
    IN
        SELECT
            i.ingredient_id,
            i.current_quantity,
            (
                pr.quantity_required
                * p_product_quantity
            )::NUMERIC(12,3)
        FROM product_recipes AS pr
        INNER JOIN ingredients AS i
            ON i.ingredient_id = pr.ingredient_id
        WHERE pr.product_id = p_product_id
        ORDER BY i.ingredient_id
        FOR UPDATE OF i
    LOOP

        IF v_current_quantity < v_required_quantity THEN

            RAISE EXCEPTION
                'Insufficient inventory for ingredient ID %. Required: %, Available: %.',
                v_ingredient_id,
                v_required_quantity,
                v_current_quantity;

        END IF;

    END LOOP;


    /*
     * ---------------------------------------------------------
     * SECOND PASS
     *
     * Now that EVERY ingredient has been verified and locked,
     * deduct each ingredient using the central inventory
     * transaction function.
     * ---------------------------------------------------------
     */

    FOR
        v_ingredient_id,
        v_ingredient_name,
        v_current_quantity,
        v_required_quantity
    IN
        SELECT
            i.ingredient_id,
            i.ingredient_name,
            i.current_quantity,
            (
                pr.quantity_required
                * p_product_quantity
            )::NUMERIC(12,3)
        FROM product_recipes AS pr
        INNER JOIN ingredients AS i
            ON i.ingredient_id = pr.ingredient_id
        WHERE pr.product_id = p_product_id
        ORDER BY i.ingredient_id
        FOR UPDATE OF i
    LOOP

        SELECT
            t.transaction_id,
            t.quantity_before,
            t.quantity_after
        INTO
            v_transaction_id,
            v_quantity_before,
            v_quantity_after
        FROM apply_inventory_transaction(
            v_ingredient_id,
            p_user_id,
            'ORDER_DEDUCTION',
            -v_required_quantity,
            p_order_id,
            'Automatic recipe deduction for product ID '
                || p_product_id
                || ', quantity '
                || p_product_quantity
        ) AS t;


        RETURN QUERY
        SELECT
            v_transaction_id,
            v_ingredient_id,
            v_ingredient_name,
            v_quantity_before,
            v_required_quantity,
            v_quantity_after;

    END LOOP;

END;
$$;


--
-- Name: reverse_order_recipe_inventory(integer, integer, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.reverse_order_recipe_inventory(p_order_id integer, p_user_id integer, p_reason text) RETURNS TABLE(transaction_id integer, ingredient_id integer, ingredient_name character varying, quantity_before numeric, quantity_reversed numeric, quantity_after numeric)
    LANGUAGE plpgsql
    AS $$

DECLARE

    v_payment_status VARCHAR(20);

    v_has_deduction BOOLEAN;

    v_ingredient_id INTEGER;
    v_ingredient_name VARCHAR(100);

    v_remaining_quantity NUMERIC(12,3);

    v_transaction_id INTEGER;
    v_quantity_before NUMERIC(12,3);
    v_quantity_after NUMERIC(12,3);

BEGIN

    /*
     * ---------------------------------------------------------
     * VALIDATION
     * ---------------------------------------------------------
     */

    IF p_order_id <= 0 THEN

        RAISE EXCEPTION
            'Invalid order ID.';

    END IF;


    IF p_user_id <= 0 THEN

        RAISE EXCEPTION
            'Invalid user ID.';

    END IF;


    IF p_reason IS NULL
       OR trim(p_reason) = '' THEN

        RAISE EXCEPTION
            'Reversal reason is required.';

    END IF;


    /*
     * ---------------------------------------------------------
     * GET ORDER
     *
     * Lock the order so another operation cannot change
     * its payment state while the reversal is being calculated.
     * ---------------------------------------------------------
     */

    SELECT
        o.payment_status
    INTO
        v_payment_status
    FROM orders AS o
    WHERE o.order_id = p_order_id
    FOR UPDATE;


    IF NOT FOUND THEN

        RAISE EXCEPTION
            'Order ID % does not exist.',
            p_order_id;

    END IF;


    /*
     * ---------------------------------------------------------
     * ONLY UNPAID ORDERS
     *
     * Paid orders require a separate financial/refund process.
     * ---------------------------------------------------------
     */

    IF v_payment_status <> 'UNPAID' THEN

        RAISE EXCEPTION
            'Order % is not unpaid. Inventory release is only allowed for unpaid orders.',
            p_order_id;

    END IF;


    /*
     * ---------------------------------------------------------
     * VERIFY ORIGINAL DEDUCTIONS EXIST
     * ---------------------------------------------------------
     */

    SELECT EXISTS (
        SELECT 1
        FROM inventory_transactions AS it
        WHERE it.order_id = p_order_id
          AND it.transaction_type = 'ORDER_DEDUCTION'
    )
    INTO v_has_deduction;


    IF NOT v_has_deduction THEN

        RAISE EXCEPTION
            'No recipe inventory deductions were found for order %.',

            p_order_id;

    END IF;


    /*
     * ---------------------------------------------------------
     * FIND REMAINING INVENTORY RESERVATION
     *
     * IMPORTANT:
     *
     * remaining =
     *
     *     total ORDER_DEDUCTION
     *     -
     *     total REVERSAL
     *
     * This allows an order to be edited multiple times without
     * causing cancellation to restore too much inventory.
     * ---------------------------------------------------------
     */

    FOR
        v_ingredient_id,
        v_ingredient_name,
        v_remaining_quantity
    IN

        WITH inventory_balance AS (

            SELECT

                it.ingredient_id,

                SUM(
                    CASE
                        WHEN it.transaction_type =
                            'ORDER_DEDUCTION'
                        THEN ABS(it.quantity_change)

                        ELSE 0
                    END
                )::NUMERIC(12,3)
                AS total_deducted,

                SUM(
                    CASE
                        WHEN it.transaction_type =
                            'REVERSAL'
                        THEN ABS(it.quantity_change)

                        ELSE 0
                    END
                )::NUMERIC(12,3)
                AS total_reversed

            FROM inventory_transactions AS it

            WHERE it.order_id = p_order_id

              AND it.transaction_type IN (
                  'ORDER_DEDUCTION',
                  'REVERSAL'
              )

            GROUP BY
                it.ingredient_id

        )

        SELECT

            ib.ingredient_id,

            i.ingredient_name,

            (
                ib.total_deducted
                -
                ib.total_reversed
            )::NUMERIC(12,3)

        FROM inventory_balance AS ib

        INNER JOIN ingredients AS i
            ON i.ingredient_id =
               ib.ingredient_id

        WHERE
            (
                ib.total_deducted
                -
                ib.total_reversed
            ) > 0

        ORDER BY
            ib.ingredient_id

    LOOP


        /*
         * -----------------------------------------------------
         * APPLY REMAINING REVERSAL
         * -----------------------------------------------------
         */

        SELECT
            t.transaction_id,
            t.quantity_before,
            t.quantity_after

        INTO
            v_transaction_id,
            v_quantity_before,
            v_quantity_after

        FROM apply_inventory_transaction(
            v_ingredient_id,
            p_user_id,
            'REVERSAL',
            v_remaining_quantity,
            p_order_id,
            p_reason
        ) AS t;


        /*
         * -----------------------------------------------------
         * RETURN REVERSAL INFORMATION
         * -----------------------------------------------------
         */

        RETURN QUERY

        SELECT

            v_transaction_id,

            v_ingredient_id,

            v_ingredient_name,

            v_quantity_before,

            v_remaining_quantity,

            v_quantity_after;


    END LOOP;


END;

$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: api_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.api_tokens (
    token_id bigint NOT NULL,
    user_id integer NOT NULL,
    token_hash character(64) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    revoked_at timestamp without time zone
);


--
-- Name: api_tokens_token_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.api_tokens_token_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: api_tokens_token_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.api_tokens_token_id_seq OWNED BY public.api_tokens.token_id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    audit_id bigint NOT NULL,
    user_id bigint,
    action_type character varying(100) NOT NULL,
    module character varying(100) NOT NULL,
    target_type character varying(100),
    target_id bigint,
    description text NOT NULL,
    metadata jsonb,
    ip_address inet,
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: audit_logs_audit_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_audit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_audit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_audit_id_seq OWNED BY public.audit_logs.audit_id;


--
-- Name: ingredients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ingredients (
    ingredient_id integer NOT NULL,
    ingredient_name character varying(100) NOT NULL,
    description text,
    unit_id integer NOT NULL,
    current_quantity numeric(12,3) DEFAULT 0 NOT NULL,
    minimum_quantity numeric(12,3) DEFAULT 0 NOT NULL,
    is_available boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT ingredients_current_quantity_check CHECK ((current_quantity >= (0)::numeric)),
    CONSTRAINT ingredients_minimum_quantity_check CHECK ((minimum_quantity >= (0)::numeric))
);


--
-- Name: ingredients_ingredient_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ingredients_ingredient_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ingredients_ingredient_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ingredients_ingredient_id_seq OWNED BY public.ingredients.ingredient_id;


--
-- Name: inventory; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory (
    inventory_id integer NOT NULL,
    product_id integer NOT NULL,
    quantity integer DEFAULT 0 NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: inventory_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_inventory_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_inventory_id_seq OWNED BY public.inventory.inventory_id;


--
-- Name: inventory_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_transactions (
    transaction_id integer NOT NULL,
    ingredient_id integer NOT NULL,
    user_id integer,
    transaction_type character varying(30) NOT NULL,
    quantity_change numeric(12,3) NOT NULL,
    quantity_before numeric(12,3) NOT NULL,
    quantity_after numeric(12,3) NOT NULL,
    order_id integer,
    reason text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT inventory_transactions_after_check CHECK ((quantity_after >= (0)::numeric)),
    CONSTRAINT inventory_transactions_before_check CHECK ((quantity_before >= (0)::numeric)),
    CONSTRAINT inventory_transactions_change_check CHECK ((quantity_change <> (0)::numeric)),
    CONSTRAINT inventory_transactions_type_check CHECK (((transaction_type)::text = ANY ((ARRAY['STOCK_IN'::character varying, 'STOCK_OUT'::character varying, 'ADJUSTMENT'::character varying, 'WASTE'::character varying, 'ORDER_DEDUCTION'::character varying, 'REVERSAL'::character varying])::text[])))
);


--
-- Name: inventory_transactions_transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_transactions_transaction_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_transactions_transaction_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_transactions_transaction_id_seq OWNED BY public.inventory_transactions.transaction_id;


--
-- Name: inventory_units; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_units (
    unit_id integer NOT NULL,
    unit_name character varying(50) NOT NULL,
    unit_code character varying(20) NOT NULL,
    unit_type character varying(30) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT inventory_units_unit_type_check CHECK (((unit_type)::text = ANY ((ARRAY['WEIGHT'::character varying, 'VOLUME'::character varying, 'COUNT'::character varying])::text[])))
);


--
-- Name: inventory_units_unit_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_units_unit_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_units_unit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_units_unit_id_seq OWNED BY public.inventory_units.unit_id;


--
-- Name: login_activity; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_activity (
    login_activity_id integer NOT NULL,
    user_id integer NOT NULL,
    login_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: login_activity_login_activity_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.login_activity_login_activity_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: login_activity_login_activity_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.login_activity_login_activity_id_seq OWNED BY public.login_activity.login_activity_id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    notification_id integer NOT NULL,
    user_id integer NOT NULL,
    order_id integer,
    title character varying(150) NOT NULL,
    message text NOT NULL,
    notification_type character varying(50) NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    is_resolved boolean DEFAULT false NOT NULL
);


--
-- Name: notifications_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notifications_notification_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notifications_notification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notifications_notification_id_seq OWNED BY public.notifications.notification_id;


--
-- Name: order_action_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_action_logs (
    action_id bigint NOT NULL,
    order_id integer NOT NULL,
    user_id integer NOT NULL,
    action_type character varying(30) NOT NULL,
    reason text NOT NULL,
    previous_order_status character varying(30),
    new_order_status character varying(30),
    previous_payment_status character varying(20),
    new_payment_status character varying(20),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_order_action_logs_action_type CHECK (((action_type)::text = ANY ((ARRAY['CANCEL'::character varying, 'EDIT'::character varying, 'REDO'::character varying, 'REFUND'::character varying])::text[]))),
    CONSTRAINT chk_order_action_logs_reason CHECK ((length(TRIM(BOTH FROM reason)) > 0))
);


--
-- Name: order_action_logs_action_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_action_logs_action_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_action_logs_action_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_action_logs_action_id_seq OWNED BY public.order_action_logs.action_id;


--
-- Name: order_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_items (
    order_item_id integer NOT NULL,
    order_id integer NOT NULL,
    product_id integer NOT NULL,
    quantity integer NOT NULL,
    unit_price numeric(10,2) NOT NULL
);


--
-- Name: order_items_order_item_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_items_order_item_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_items_order_item_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_items_order_item_id_seq OWNED BY public.order_items.order_item_id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    order_id integer NOT NULL,
    user_id integer,
    total_amount numeric(10,2) NOT NULL,
    order_status character varying(30) DEFAULT 'Pending'::character varying,
    order_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    payment_method character varying(20) DEFAULT 'CASHIER'::character varying,
    payment_status character varying(20) DEFAULT 'UNPAID'::character varying,
    order_source character varying(20) DEFAULT 'ONLINE_APP'::character varying,
    maya_checkout_id character varying(100),
    maya_payment_id character varying(100),
    paid_at timestamp without time zone,
    inventory_deducted boolean DEFAULT false NOT NULL,
    shift_id bigint
);


--
-- Name: orders_order_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orders_order_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: orders_order_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_order_id_seq OWNED BY public.orders.order_id;


--
-- Name: product_recipes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_recipes (
    recipe_id integer NOT NULL,
    product_id integer NOT NULL,
    ingredient_id integer NOT NULL,
    quantity_required numeric(12,3) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT product_recipes_quantity_check CHECK ((quantity_required > (0)::numeric))
);


--
-- Name: product_recipes_recipe_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_recipes_recipe_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_recipes_recipe_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_recipes_recipe_id_seq OWNED BY public.product_recipes.recipe_id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    product_id integer NOT NULL,
    product_name character varying(100) NOT NULL,
    description text,
    category character varying(50) NOT NULL,
    price numeric(10,2) NOT NULL,
    image_url text,
    stock_quantity integer DEFAULT 0,
    is_available boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: products_product_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_product_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_product_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_product_id_seq OWNED BY public.products.product_id;


--
-- Name: staff_shifts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_shifts (
    shift_id bigint NOT NULL,
    user_id bigint NOT NULL,
    started_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ended_at timestamp with time zone,
    status character varying(20) DEFAULT 'OPEN'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT staff_shifts_end_check CHECK (((((status)::text = 'OPEN'::text) AND (ended_at IS NULL)) OR (((status)::text = 'CLOSED'::text) AND (ended_at IS NOT NULL)))),
    CONSTRAINT staff_shifts_status_check CHECK (((status)::text = ANY ((ARRAY['OPEN'::character varying, 'CLOSED'::character varying])::text[])))
);


--
-- Name: staff_shifts_shift_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.staff_shifts_shift_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staff_shifts_shift_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.staff_shifts_shift_id_seq OWNED BY public.staff_shifts.shift_id;


--
-- Name: user_fcm_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_fcm_tokens (
    fcm_token_id integer NOT NULL,
    user_id integer NOT NULL,
    fcm_token text NOT NULL,
    device_name character varying(100),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: user_fcm_tokens_fcm_token_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_fcm_tokens_fcm_token_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_fcm_tokens_fcm_token_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_fcm_tokens_fcm_token_id_seq OWNED BY public.user_fcm_tokens.fcm_token_id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    user_id integer NOT NULL,
    full_name character varying(100) NOT NULL,
    email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    phone character varying(20),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    role character varying(20) DEFAULT 'CUSTOMER'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    address text,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['CUSTOMER'::character varying, 'STAFF'::character varying, 'ADMIN'::character varying])::text[])))
);


--
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_user_id_seq OWNED BY public.users.user_id;


--
-- Name: website_updates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.website_updates (
    update_id integer NOT NULL,
    title character varying(150) NOT NULL,
    description text NOT NULL,
    update_type character varying(30) DEFAULT 'PROMO'::character varying NOT NULL,
    image_url text,
    start_date date,
    end_date date,
    is_published boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT website_updates_type_check CHECK (((update_type)::text = ANY ((ARRAY['PROMO'::character varying, 'ANNOUNCEMENT'::character varying, 'NEW_PRODUCT'::character varying, 'EVENT'::character varying])::text[])))
);


--
-- Name: website_updates_update_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.website_updates_update_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: website_updates_update_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.website_updates_update_id_seq OWNED BY public.website_updates.update_id;


--
-- Name: api_tokens token_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens ALTER COLUMN token_id SET DEFAULT nextval('public.api_tokens_token_id_seq'::regclass);


--
-- Name: audit_logs audit_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN audit_id SET DEFAULT nextval('public.audit_logs_audit_id_seq'::regclass);


--
-- Name: ingredients ingredient_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ingredients ALTER COLUMN ingredient_id SET DEFAULT nextval('public.ingredients_ingredient_id_seq'::regclass);


--
-- Name: inventory inventory_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory ALTER COLUMN inventory_id SET DEFAULT nextval('public.inventory_inventory_id_seq'::regclass);


--
-- Name: inventory_transactions transaction_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_transactions ALTER COLUMN transaction_id SET DEFAULT nextval('public.inventory_transactions_transaction_id_seq'::regclass);


--
-- Name: inventory_units unit_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_units ALTER COLUMN unit_id SET DEFAULT nextval('public.inventory_units_unit_id_seq'::regclass);


--
-- Name: login_activity login_activity_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_activity ALTER COLUMN login_activity_id SET DEFAULT nextval('public.login_activity_login_activity_id_seq'::regclass);


--
-- Name: notifications notification_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications ALTER COLUMN notification_id SET DEFAULT nextval('public.notifications_notification_id_seq'::regclass);


--
-- Name: order_action_logs action_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_action_logs ALTER COLUMN action_id SET DEFAULT nextval('public.order_action_logs_action_id_seq'::regclass);


--
-- Name: order_items order_item_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items ALTER COLUMN order_item_id SET DEFAULT nextval('public.order_items_order_item_id_seq'::regclass);


--
-- Name: orders order_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN order_id SET DEFAULT nextval('public.orders_order_id_seq'::regclass);


--
-- Name: product_recipes recipe_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes ALTER COLUMN recipe_id SET DEFAULT nextval('public.product_recipes_recipe_id_seq'::regclass);


--
-- Name: products product_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN product_id SET DEFAULT nextval('public.products_product_id_seq'::regclass);


--
-- Name: staff_shifts shift_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_shifts ALTER COLUMN shift_id SET DEFAULT nextval('public.staff_shifts_shift_id_seq'::regclass);


--
-- Name: user_fcm_tokens fcm_token_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_fcm_tokens ALTER COLUMN fcm_token_id SET DEFAULT nextval('public.user_fcm_tokens_fcm_token_id_seq'::regclass);


--
-- Name: users user_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN user_id SET DEFAULT nextval('public.users_user_id_seq'::regclass);


--
-- Name: website_updates update_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.website_updates ALTER COLUMN update_id SET DEFAULT nextval('public.website_updates_update_id_seq'::regclass);


--
-- Name: api_tokens api_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_pkey PRIMARY KEY (token_id);


--
-- Name: api_tokens api_tokens_token_hash_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_token_hash_key UNIQUE (token_hash);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (audit_id);


--
-- Name: ingredients ingredients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ingredients
    ADD CONSTRAINT ingredients_pkey PRIMARY KEY (ingredient_id);


--
-- Name: inventory inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_pkey PRIMARY KEY (inventory_id);


--
-- Name: inventory inventory_product_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_product_id_key UNIQUE (product_id);


--
-- Name: inventory_transactions inventory_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_transactions
    ADD CONSTRAINT inventory_transactions_pkey PRIMARY KEY (transaction_id);


--
-- Name: inventory_units inventory_units_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_units
    ADD CONSTRAINT inventory_units_pkey PRIMARY KEY (unit_id);


--
-- Name: inventory_units inventory_units_unit_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_units
    ADD CONSTRAINT inventory_units_unit_code_key UNIQUE (unit_code);


--
-- Name: login_activity login_activity_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_activity
    ADD CONSTRAINT login_activity_pkey PRIMARY KEY (login_activity_id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (notification_id);


--
-- Name: order_action_logs order_action_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_action_logs
    ADD CONSTRAINT order_action_logs_pkey PRIMARY KEY (action_id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (order_item_id);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (order_id);


--
-- Name: product_recipes product_recipes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT product_recipes_pkey PRIMARY KEY (recipe_id);


--
-- Name: product_recipes product_recipes_unique_item; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT product_recipes_unique_item UNIQUE (product_id, ingredient_id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (product_id);


--
-- Name: staff_shifts staff_shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_shifts
    ADD CONSTRAINT staff_shifts_pkey PRIMARY KEY (shift_id);


--
-- Name: user_fcm_tokens user_fcm_tokens_fcm_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_fcm_tokens
    ADD CONSTRAINT user_fcm_tokens_fcm_token_key UNIQUE (fcm_token);


--
-- Name: user_fcm_tokens user_fcm_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_fcm_tokens
    ADD CONSTRAINT user_fcm_tokens_pkey PRIMARY KEY (fcm_token_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: website_updates website_updates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.website_updates
    ADD CONSTRAINT website_updates_pkey PRIMARY KEY (update_id);


--
-- Name: idx_api_tokens_expires_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_api_tokens_expires_at ON public.api_tokens USING btree (expires_at);


--
-- Name: idx_api_tokens_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_api_tokens_user_id ON public.api_tokens USING btree (user_id);


--
-- Name: idx_inventory_transactions_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_inventory_transactions_created_at ON public.inventory_transactions USING btree (created_at);


--
-- Name: idx_inventory_transactions_ingredient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_inventory_transactions_ingredient ON public.inventory_transactions USING btree (ingredient_id);


--
-- Name: idx_inventory_transactions_order; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_inventory_transactions_order ON public.inventory_transactions USING btree (order_id);


--
-- Name: idx_inventory_transactions_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_inventory_transactions_user ON public.inventory_transactions USING btree (user_id);


--
-- Name: idx_notifications_order; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_order ON public.notifications USING btree (order_id);


--
-- Name: idx_notifications_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_user ON public.notifications USING btree (user_id);


--
-- Name: idx_notifications_user_unread; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_user_unread ON public.notifications USING btree (user_id, is_read);


--
-- Name: idx_order_action_logs_action_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_order_action_logs_action_type ON public.order_action_logs USING btree (action_type);


--
-- Name: idx_order_action_logs_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_order_action_logs_created_at ON public.order_action_logs USING btree (created_at);


--
-- Name: idx_order_action_logs_order_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_order_action_logs_order_id ON public.order_action_logs USING btree (order_id);


--
-- Name: idx_order_action_logs_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_order_action_logs_user_id ON public.order_action_logs USING btree (user_id);


--
-- Name: idx_orders_shift_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_orders_shift_id ON public.orders USING btree (shift_id);


--
-- Name: idx_staff_shifts_one_open_per_user; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_staff_shifts_one_open_per_user ON public.staff_shifts USING btree (user_id) WHERE ((status)::text = 'OPEN'::text);


--
-- Name: idx_staff_shifts_one_open_shift; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_staff_shifts_one_open_shift ON public.staff_shifts USING btree (status) WHERE ((status)::text = 'OPEN'::text);


--
-- Name: idx_user_fcm_tokens_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_fcm_tokens_user ON public.user_fcm_tokens USING btree (user_id);


--
-- Name: api_tokens api_tokens_user_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_user_fk FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE CASCADE;


--
-- Name: audit_logs fk_audit_logs_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: ingredients fk_ingredients_unit; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ingredients
    ADD CONSTRAINT fk_ingredients_unit FOREIGN KEY (unit_id) REFERENCES public.inventory_units(unit_id) ON DELETE RESTRICT;


--
-- Name: inventory_transactions fk_inventory_transactions_ingredient; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_transactions
    ADD CONSTRAINT fk_inventory_transactions_ingredient FOREIGN KEY (ingredient_id) REFERENCES public.ingredients(ingredient_id) ON DELETE RESTRICT;


--
-- Name: inventory_transactions fk_inventory_transactions_order; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_transactions
    ADD CONSTRAINT fk_inventory_transactions_order FOREIGN KEY (order_id) REFERENCES public.orders(order_id) ON DELETE SET NULL;


--
-- Name: inventory_transactions fk_inventory_transactions_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_transactions
    ADD CONSTRAINT fk_inventory_transactions_user FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE SET NULL;


--
-- Name: login_activity fk_login_activity_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_activity
    ADD CONSTRAINT fk_login_activity_user FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE CASCADE;


--
-- Name: notifications fk_notifications_order; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT fk_notifications_order FOREIGN KEY (order_id) REFERENCES public.orders(order_id) ON DELETE SET NULL;


--
-- Name: notifications fk_notifications_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE CASCADE;


--
-- Name: order_action_logs fk_order_action_logs_order; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_action_logs
    ADD CONSTRAINT fk_order_action_logs_order FOREIGN KEY (order_id) REFERENCES public.orders(order_id) ON DELETE RESTRICT;


--
-- Name: order_action_logs fk_order_action_logs_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_action_logs
    ADD CONSTRAINT fk_order_action_logs_user FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE RESTRICT;


--
-- Name: product_recipes fk_product_recipes_ingredient; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT fk_product_recipes_ingredient FOREIGN KEY (ingredient_id) REFERENCES public.ingredients(ingredient_id) ON DELETE RESTRICT;


--
-- Name: product_recipes fk_product_recipes_product; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT fk_product_recipes_product FOREIGN KEY (product_id) REFERENCES public.products(product_id) ON DELETE CASCADE;


--
-- Name: user_fcm_tokens fk_user_fcm_tokens_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_fcm_tokens
    ADD CONSTRAINT fk_user_fcm_tokens_user FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE CASCADE;


--
-- Name: inventory inventory_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(product_id);


--
-- Name: order_items order_items_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(order_id) ON DELETE CASCADE;


--
-- Name: order_items order_items_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(product_id);


--
-- Name: orders orders_shift_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_shift_id_fkey FOREIGN KEY (shift_id) REFERENCES public.staff_shifts(shift_id) ON DELETE RESTRICT;


--
-- Name: orders orders_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(user_id);


--
-- Name: staff_shifts staff_shifts_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_shifts
    ADD CONSTRAINT staff_shifts_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(user_id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict M2NBzrkogTNDo04OmlcSPrDccaBqfrrjGr2twNRj4ZcSKEwdNs2lUnxQF7UGEcv
