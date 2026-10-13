-- KohiCebu sample development data
-- Fictional product records for demonstration purposes only.
-- Run only after importing kohicebu_schema.sql into a development database.
-- This script does not contain real customer or order information.

BEGIN;

INSERT INTO public.products
    (product_name, description, category, price, image_url, stock_quantity, is_available)
VALUES
    (
        'Sample Iced Americano',
        'Espresso with chilled water and ice.',
        'Coffee',
        110.00,
        NULL,
        20,
        TRUE
    ),
    (
        'Sample Cafe Latte',
        'Espresso combined with steamed milk.',
        'Coffee',
        135.00,
        NULL,
        20,
        TRUE
    ),
    (
        'Sample Matcha Latte',
        'Matcha blended with milk.',
        'Non-Coffee',
        145.00,
        NULL,
        15,
        TRUE
    );

COMMIT;