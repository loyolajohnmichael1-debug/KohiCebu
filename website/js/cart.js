document.addEventListener('DOMContentLoaded', () => {
    const loading = document.getElementById('cartLoading');
    const empty = document.getElementById('cartEmpty');
    const layout = document.getElementById('cartLayout');
    const error = document.getElementById('cartError');
    const list = document.getElementById('cartItemsList');
    const checkout = document.getElementById('checkoutButton');
    const retry = document.getElementById('retryCart');
    let cart = [];

    const readCart = () => {
        try {
            const parsed = JSON.parse(localStorage.getItem('kohiCebuCart') || '[]');
            cart = Array.isArray(parsed) ? parsed.filter(item =>
                item && Number.isInteger(Number(item.product_id)) && Number(item.product_id) > 0 &&
                Number.isInteger(Number(item.quantity)) && Number(item.quantity) > 0
            ) : [];
            localStorage.setItem('kohiCebuCart', JSON.stringify(cart));
            render();
        } catch (e) {
            loading.hidden = true;
            layout.hidden = true;
            empty.hidden = true;
            error.hidden = false;
        }
    };

    const money = value => new Intl.NumberFormat('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2
    }).format(value);

    const imagePath = value => {
        const source = String(value || '').trim();
        if (!source) return '';
        if (/^https:\/\//i.test(source) || source.startsWith('/')) return source;
        if (/^http:/i.test(source) || /^data:/i.test(source) || /^javascript:/i.test(source)) return '';
        return '../' + source.replace(/^\.?\//, '');
    };

    const render = () => {
        loading.hidden = true;
        error.hidden = true;
        if (!cart.length) {
            empty.hidden = false;
            layout.hidden = true;
            return;
        }

        empty.hidden = true;
        layout.hidden = false;
        list.replaceChildren();
        let count = 0;
        let total = 0;

        cart.forEach((item, index) => {
            const quantity = Number(item.quantity);
            const price = Number(item.price);
            const safePrice = Number.isFinite(price) && price >= 0 ? price : 0;
            const name = String(item.product_name || 'Product');
            const subtotal = safePrice * quantity;
            count += quantity;
            total += subtotal;

            const row = document.createElement('article');
            row.className = 'cart-item';
            const imageBox = document.createElement('div');
            imageBox.className = 'cart-item__image';
            const src = imagePath(item.image_url);
            if (src) {
                const image = document.createElement('img');
                image.src = src;
                image.alt = name;
                image.loading = 'lazy';
                imageBox.append(image);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'cart-item__placeholder';
                placeholder.textContent = 'KOHI CEBU';
                imageBox.append(placeholder);
            }

            const info = document.createElement('div');
            const category = document.createElement('p');
            category.className = 'cart-item__category';
            category.textContent = String(item.category || '');
            const title = document.createElement('h3');
            title.className = 'cart-item__name';
            title.textContent = name;
            const priceText = document.createElement('p');
            priceText.className = 'cart-item__price';
            priceText.textContent = money(safePrice);
            info.append(category, title, priceText);

            const controls = document.createElement('div');
            controls.className = 'cart-item__controls';
            const quantityBox = document.createElement('div');
            quantityBox.className = 'cart-item__quantity';
            const decrease = document.createElement('button');
            decrease.type = 'button';
            decrease.textContent = '−';
            decrease.setAttribute('aria-label', `Decrease ${name} quantity`);
            decrease.disabled = quantity <= 1;
            decrease.addEventListener('click', () => update(index, quantity - 1));
            const quantityText = document.createElement('span');
            quantityText.textContent = String(quantity);
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.textContent = '+';
            increase.setAttribute('aria-label', `Increase ${name} quantity`);
            increase.addEventListener('click', () => update(index, quantity + 1));
            quantityBox.append(decrease, quantityText, increase);
            const itemTotal = document.createElement('strong');
            itemTotal.className = 'cart-item__total';
            itemTotal.textContent = money(subtotal);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'cart-item__remove';
            remove.textContent = 'Remove';
            remove.addEventListener('click', () => update(index, 0));
            controls.append(quantityBox, itemTotal, remove);
            row.append(imageBox, info, controls);
            list.append(row);
        });

        document.getElementById('cartItemCount').textContent = String(count);
        document.getElementById('cartSubtotal').textContent = money(total);
        document.getElementById('cartTotal').textContent = money(total);
    };

    const update = (index, quantity) => {
        if (quantity < 1) cart.splice(index, 1);
        else cart[index].quantity = quantity;
        localStorage.setItem('kohiCebuCart', JSON.stringify(cart));
        render();
    };

    checkout?.addEventListener('click', () => {
        if (cart.length) window.location.href = 'checkout.php';
    });
    retry?.addEventListener('click', readCart);
    window.addEventListener('storage', event => {
        if (event.key === 'kohiCebuCart') readCart();
    });
    readCart();
});
