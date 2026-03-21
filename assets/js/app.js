// assets/js/app.js

// Load cart from localStorage or start empty
let cart = JSON.parse(localStorage.getItem('objsis_cart')) || [];
let currentCoupon = null; // Store applied coupon

// Initialize UI when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
});

function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    saveCart();
    updateCartUI();
    showFlash(`Added ${name} to cart`);
}

function saveCart() {
    localStorage.setItem('objsis_cart', JSON.stringify(cart));
}

function updateCartUI() {
    const cartCount = document.getElementById('cart-count');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const cartSubtotal = document.getElementById('cart-subtotal');
    const cartDiscount = document.getElementById('cart-discount');
    const discountRow = document.getElementById('discount-row');
    const cartBtn = document.getElementById('floating-cart-btn');
    const cartModal = document.getElementById('cart-modal');

    // Guard items
    if (!cartCount) return;

    // Update Count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.innerText = totalItems;

    // Show/Hide Cart Button
    if (totalItems > 0) {
        cartBtn.style.display = 'flex';
    } else {
        cartBtn.style.display = 'none';
        if (cartModal) cartModal.style.display = 'none';
        currentCoupon = null; // Reset coupon if empty
    }

    // Calculations
    let total = 0;
    cart.forEach(item => total += item.price * item.quantity);

    // Default Final
    let finalTotal = total;
    let discountAmount = 0;

    // Apply Coupon Logic Locally for Preview
    if (currentCoupon) {
        if (currentCoupon.type === 'fixed') {
            discountAmount = Math.min(total, currentCoupon.value);
        } else if (currentCoupon.type === 'percent') {
            discountAmount = total * (currentCoupon.value / 100);
        }
        finalTotal = Math.max(0, total - discountAmount);

        // Update UI for Discount
        if (cartDiscount) cartDiscount.innerText = `-${discountAmount.toFixed(2)} €`;
        if (discountRow) discountRow.style.display = 'flex';

        // Show success message input area
        const msg = document.getElementById('coupon-msg');
        if (msg) {
            msg.style.color = 'var(--success)';
            msg.innerText = `Coupon '${currentCoupon.code}' applied!`;
        }
    } else {
        if (discountRow) discountRow.style.display = 'none';
        const msg = document.getElementById('coupon-msg');
        if (msg) msg.innerText = '';
    }

    // Update Text
    if (cartSubtotal) cartSubtotal.innerText = `${total.toFixed(2)} €`;
    if (cartTotal) cartTotal.innerText = finalTotal.toFixed(2);

    // Update List
    if (cartItems) {
        cartItems.innerHTML = '';
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.style.display = 'flex';
            div.style.justifyContent = 'space-between';
            div.style.alignItems = 'center';
            div.style.padding = '12px 0';
            div.style.borderBottom = '1px solid rgba(255,255,255,0.1)';

            div.innerHTML = `
                <div>
                    <span style="color:var(--primary-color); font-weight:bold; margin-right:8px;">${item.quantity}x</span> 
                    <span>${item.name}</span>
                </div>
                <span>${itemTotal.toFixed(2)} €</span>
            `;
            cartItems.appendChild(div);
        });
    }
}

function applyCoupon() {
    const input = document.getElementById('coupon-input');
    const msg = document.getElementById('coupon-msg');
    const code = input.value.trim();

    if (!code) return;

    // Calculate current subtotal for validation
    let subtotal = 0;
    cart.forEach(item => subtotal += item.price * item.quantity);

    // Using FormData to match PHP $_POST expectations if untyped, 
    // but verify_coupon.php reads $_POST. 
    // fetch with JSON body requires php header('Content-Type: application/json') and json_decode(input).
    // The previous verify_coupon.php I wrote used $_POST directly (standard form post). 
    // So let's use FormData or URLSearchParams.

    const formData = new FormData();
    formData.append('code', code);
    formData.append('cart_total', subtotal);

    fetch('api/verify_coupon.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentCoupon = data; // Store entire coupon data including type/value
                updateCartUI();
                showFlash("Coupon Applied!");
            } else {
                currentCoupon = null;
                updateCartUI();
                if (msg) {
                    msg.style.color = 'var(--danger)';
                    msg.innerText = data.message;
                }
            }
        })
        .catch(err => alert("Error checking coupon"));
}

function toggleCart() {
    const modal = document.getElementById('cart-modal');
    if (modal) {
        modal.classList.toggle('open');
    }
}

function placeOrder() {
    const urlParams = new URLSearchParams(window.location.search);
    const tableNumber = urlParams.get('table');

    if (!tableNumber) {
        alert("Error: Table number missing.");
        return;
    }

    if (cart.length === 0) return;

    fetch('api/create_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            table_number: tableNumber,
            items: cart,
            coupon_code: currentCoupon ? currentCoupon.code : null
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cart = [];
                currentCoupon = null;
                saveCart();
                updateCartUI();
                toggleCart(); // Close drawer
                alert(`Order Placed Successfully! Order ID: ${data.order_id}`);
            } else {
                alert("Failed to place order: " + data.message);
            }
        })
        .catch(err => alert("Error processing order: " + err));
}

function showFlash(msg) {
    const toast = document.createElement('div');
    toast.innerText = msg;

    Object.assign(toast.style, {
        position: 'fixed', bottom: '100px', left: '50%', transform: 'translateX(-50%)',
        background: 'rgba(15, 23, 42, 0.9)', color: '#fff', padding: '12px 24px',
        borderRadius: '30px', zIndex: '2000', border: '1px solid rgba(255,255,255,0.1)',
        boxShadow: '0 4px 12px rgba(0,0,0,0.3)', opacity: '0', transition: 'opacity 0.3s ease, transform 0.3s ease'
    });

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translate(-50%, -10px)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}
