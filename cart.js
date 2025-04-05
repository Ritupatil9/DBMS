// Cart state management
let cart = [];
let isLoading = false;

// DOM Elements
const cartItemsDiv = document.getElementById("cart-items");
const totalSection = document.getElementById("total-section");
const subtotalElement = document.getElementById("subtotal");
const shippingElement = document.getElementById("shipping");
const totalPriceElement = document.getElementById("total-price");

// Constants
const SHIPPING_COST = 50; // Fixed shipping cost in rupees
const API_BASE_URL = 'cart_api.php';

// Initialize cart display
async function initializeCart() {
    try {
        isLoading = true;
        showLoadingState();
        const response = await fetch(`${API_BASE_URL}?action=items`);
        const data = await response.json();
        
        if (response.ok) {
            cart = data.items;
            if (cart.length === 0) {
                displayEmptyCart();
            } else {
                displayCartItems();
                updateTotals(data.total);
            }
        } else {
            showError("Failed to load cart items");
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        showError("Failed to load cart items");
    } finally {
        isLoading = false;
        hideLoadingState();
    }
}

// Display loading state
function showLoadingState() {
    cartItemsDiv.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
}

function hideLoadingState() {
    // Loading state will be replaced by actual content
}

// Display empty cart message
function displayEmptyCart() {
    cartItemsDiv.innerHTML = `
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items to your cart yet.</p>
            <a href="index.html" class="btn btn-primary mt-3">Continue Shopping</a>
        </div>
    `;
    totalSection.style.display = "none";
}

// Display cart items
function displayCartItems() {
    cartItemsDiv.innerHTML = "";
    cart.forEach((item) => {
        const itemElement = document.createElement("div");
        itemElement.classList.add("cart-item");
        itemElement.innerHTML = `
            <img src="${item.image_url}" alt="${item.name}" class="item-image">
            <div class="item-details">
                <h5>${item.name}</h5>
                <p class="text-muted">${item.description || ''}</p>
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="updateQuantity(${item.cart_item_id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${item.cart_item_id}, 1)">+</button>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end">
                <span class="h5 mb-2">₹${item.price}</span>
                <button class="remove-btn" onclick="removeItem(${item.cart_item_id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        cartItemsDiv.appendChild(itemElement);
    });
    totalSection.style.display = "block";
}

// Update item quantity
async function updateQuantity(cartItemId, change) {
    try {
        const item = cart.find(item => item.cart_item_id === cartItemId);
        if (!item) return;

        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) return;

        const response = await fetch(`${API_BASE_URL}?action=update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart_item_id: cartItemId,
                quantity: newQuantity
            })
        });

        if (response.ok) {
            await initializeCart(); // Refresh cart data
            showNotification("Quantity updated successfully");
        } else {
            showError("Failed to update quantity");
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        showError("Failed to update quantity");
    }
}

// Remove item from cart
async function removeItem(cartItemId) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=remove`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart_item_id: cartItemId
            })
        });

        if (response.ok) {
            await initializeCart(); // Refresh cart data
            showNotification("Item removed from cart");
        } else {
            showError("Failed to remove item");
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showError("Failed to remove item");
    }
}

// Update totals
function updateTotals(subtotal) {
    const shipping = subtotal > 0 ? SHIPPING_COST : 0;
    const total = subtotal + shipping;

    subtotalElement.textContent = `₹${subtotal}`;
    shippingElement.textContent = `₹${shipping}`;
    totalPriceElement.textContent = `₹${total}`;
}

// Show notification
function showNotification(message) {
    const notification = document.createElement("div");
    notification.className = "notification";
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Show error message
function showError(message) {
    const notification = document.createElement("div");
    notification.className = "notification error";
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Proceed to checkout
async function proceedToCheckout() {
    if (cart.length === 0) {
        showError("Your cart is empty!");
        return;
    }
    
    try {
        // Here you would typically redirect to a checkout page
        // or implement your checkout logic
        window.location.href = 'checkout.html';
    } catch (error) {
        console.error('Error during checkout:', error);
        showError("Failed to proceed to checkout");
    }
}

// Add to cart function (to be called from product pages)
async function addToCart(product) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: product.id,
                quantity: 1
            })
        });

        if (response.ok) {
            showNotification("Item added to cart!");
            await initializeCart(); // Refresh cart data
        } else {
            showError("Failed to add item to cart");
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showError("Failed to add item to cart");
    }
}

// Initialize cart when page loads
document.addEventListener("DOMContentLoaded", initializeCart); 