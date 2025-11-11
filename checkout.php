<?php
// checkout.php
include 'header.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Fetch User's Saved Addresses & Payment Methods ---
try {
    // UPDATED: Now fetches phone_number along with other address details.
    $addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC");
    $addr_stmt->execute([$user_id]);
    $saved_addresses = $addr_stmt->fetchAll();

    $card_stmt = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ?");
    $card_stmt->execute([$user_id]);
    $saved_cards = $card_stmt->fetchAll();
} catch (PDOException $e) {
    $saved_addresses = [];
    $saved_cards = [];
}

// --- Fetch cart items ---
$cart_total = 0;
$stmt = $pdo->prepare("SELECT p.name, p.price, ci.quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();
if (empty($cart_items)) {
    header('Location: shop.php');
    exit;
}
foreach($cart_items as $item) $cart_total += $item['price'] * $item['quantity'];

$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>

<style>
    #new-card-form { display: none; }
    /* Accessibility class to hide elements visually but keep them available for screen readers */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }
    /* Ensure delete button doesn't interfere with card selection */
    .delete-item-btn {
        pointer-events: auto;
    }
    .saved-item-label {
        cursor: pointer;
        position: relative;
    }
</style>

<main class="bg-gray-100 py-12">
    <div class="container mx-auto px-4">
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded-lg mb-8"><span><?= $error_message ?></span></div>
        <?php endif; ?>
        <h1 class="text-3xl font-bold text-center mb-8">Checkout</h1>
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Form Column -->
            <div class="lg:col-span-2 bg-white p-8 rounded-lg shadow-md">
                <form action="place_order.php" method="POST">
                    <!-- Shipping Info -->
                    <h2 class="text-2xl font-bold mb-6 border-b pb-4">Shipping Information</h2>
                    <?php if (!empty($saved_addresses)): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-3">Use a Saved Address</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <?php foreach($saved_addresses as $addr): ?>
                            <label class="relative p-4 border rounded-lg cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500 saved-item-label">
                                <button type="button" class="delete-item-btn absolute top-2 right-2 text-gray-400 hover:text-red-500 z-10" data-id="<?= $addr['id'] ?>" data-type="address" title="Delete address">
                                    <i data-feather="x-circle" class="w-5 h-5 pointer-events-none"></i>
                                </button>
                                <input type="radio" name="saved_address" value="<?= $addr['id'] ?>" class="sr-only">
                                <p class="font-semibold pr-10"><?= htmlspecialchars($addr['address_line_1']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['zip_code']) ?></p>
                                <!-- UPDATED: Display phone number on the card -->
                                <?php if (!empty($addr['phone_number'])): ?>
                                <p class="text-sm text-gray-600 mt-1">Phone: <?= htmlspecialchars($addr['phone_number']) ?></p>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- UPDATED: "New Address" is now a clear, selectable option -->
                    <div class="mb-6">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                            <input type="radio" name="saved_address" value="new" class="h-4 w-4 text-blue-600" checked>
                            <span class="ml-3 font-medium">Enter a new address</span>
                        </label>
                    </div>

                    <!-- New Address Fields -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="address" class="block text-sm font-medium">Street Address</label>
                            <input type="text" id="address" name="address" required class="mt-1 w-full p-2 border rounded-md">
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-medium">City</label>
                            <input type="text" id="city" name="city" required class="mt-1 w-full p-2 border rounded-md">
                        </div>
                        <div>
                            <label for="zip_code" class="block text-sm font-medium">ZIP Code</label>
                            <input type="text" id="zip_code" name="zip_code" required class="mt-1 w-full p-2 border rounded-md">
                        </div>
                        <div>
                            <label for="phone_number" class="block text-sm font-medium">Phone</label>
                            <input type="tel" id="phone_number" name="phone_number" required class="mt-1 w-full p-2 border rounded-md">
                        </div>
                    </div>
                    <?php if (count($saved_addresses) < 2): ?>
                    <div class="mt-6"><label class="flex items-center"><input type="checkbox" name="save_address" value="1" class="h-4 w-4 text-blue-600 rounded"><span class="ml-2 text-sm">Save this address</span></label></div>
                    <?php endif; ?>

                    <!-- Payment Method -->
                    <div class="mt-8">
                        <h2 class="text-2xl font-bold mb-6 border-b pb-4">Payment Method</h2>
                        <div class="space-y-4">
                            <label class="flex items-center p-4 border rounded-lg has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                                <input type="radio" id="pay-card" name="payment_method" value="Card" class="h-4 w-4 text-blue-600" checked>
                                <span class="ml-3 text-sm font-medium">Credit / Debit Card</span>
                            </label>
                            <div id="card-options" class="pl-8 space-y-4">
                                <!-- Saved Cards -->
                                <?php foreach($saved_cards as $card): ?>
                                <label class="relative flex items-center p-3 border rounded-md has-[:checked]:bg-gray-100 saved-item-label">
                                    <button type="button" class="delete-item-btn absolute top-2 right-2 text-gray-400 hover:text-red-500 z-10" data-id="<?= $card['id'] ?>" data-type="card" title="Delete card">
                                        <i data-feather="x-circle" class="w-5 h-5 pointer-events-none"></i>
                                    </button>
                                    <input type="radio" name="card_choice" value="<?= $card['id'] ?>" class="h-4 w-4 text-blue-600">
                                    <span class="ml-3 text-sm pr-10"><?= htmlspecialchars($card['masked_number']) ?> (Expires <?= htmlspecialchars($card['expiry_date']) ?>)</span>
                                </label>
                                <?php endforeach; ?>
                                <!-- New Card Option -->
                                <label class="flex items-center p-3 border rounded-md has-[:checked]:bg-gray-100">
                                    <input type="radio" name="card_choice" value="new" class="h-4 w-4 text-blue-600" checked>
                                    <span class="ml-3 text-sm font-medium">Use a new card</span>
                                </label>
                                <!-- New Card Form -->
                                <div id="new-card-form" class="pt-4 space-y-4">
                                    <input type="text" name="card_number" placeholder="Card Number" class="w-full p-2 border rounded-md">
                                    <div class="grid grid-cols-2 gap-4">
                                        <input type="text" name="expiry_date" placeholder="MM / YY" class="w-full p-2 border rounded-md">
                                        <input type="text" name="cvc" placeholder="CVC" class="w-full p-2 border rounded-md">
                                    </div>
                                    <?php if (count($saved_cards) < 2): ?>
                                    <label class="flex items-center"><input type="checkbox" name="save_card" value="1" class="h-4 w-4 rounded"><span class="ml-2 text-sm">Save this card for next time</span></label>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <label class="flex items-center p-4 border rounded-lg has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                                <input type="radio" id="pay-cod" name="payment_method" value="Cash on Delivery" class="h-4 w-4 text-blue-600">
                                <span class="ml-3 text-sm font-medium">Cash on Delivery</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-8 bg-blue-600 text-white font-bold py-3 rounded-md hover:bg-blue-700">Place Order</button>
                </form>
            </div>
            <!-- Order Summary -->
            <div class="bg-white p-8 rounded-lg shadow-md h-fit">
                <h2 class="text-2xl font-bold mb-6">Order Summary</h2>
                <ul class="space-y-4 border-b pb-4">
                    <?php foreach($cart_items as $item): ?>
                    <li class="flex justify-between">
                        <span><?= htmlspecialchars($item['name']) ?> &times;<?= $item['quantity'] ?></span>
                        <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="flex justify-between font-bold text-lg mt-4">
                    <span>Total</span>
                    <span>$<?= number_format($cart_total, 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Custom Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 transition-opacity duration-300">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto transform transition-all duration-300 scale-95 opacity-0" id="delete-modal-content">
        <div class="text-center">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i data-feather="alert-triangle" class="h-6 w-6 text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirm Deletion</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500" id="delete-modal-text">
                    Are you sure you want to delete this saved item?
                </p>
            </div>
        </div>
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
            <button id="confirm-delete-btn" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:col-start-2 sm:text-sm">
                Delete
            </button>
            <button id="cancel-delete-btn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:col-start-1 sm:text-sm">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- REWRITTEN: Address Autofill & Form Management ---
    const savedAddressesData = <?= json_encode($saved_addresses) ?>;
    const addressInput = document.getElementById('address');
    const cityInput = document.getElementById('city');
    const zipInput = document.getElementById('zip_code');
    const phoneInput = document.getElementById('phone_number');

    function manageAddressFields(selectedValue) {
        if (selectedValue === 'new') {
            // Clear fields for new address entry
            addressInput.value = '';
            cityInput.value = '';
            zipInput.value = '';
            phoneInput.value = '';
            // Make fields required
            addressInput.required = true;
            cityInput.required = true;
            zipInput.required = true;
            phoneInput.required = true;
        } else {
            // Find and fill data for a saved address
            const selectedAddr = savedAddressesData.find(a => a.id == selectedValue);
            if (selectedAddr) {
                addressInput.value = selectedAddr.address_line_1 || '';
                cityInput.value = selectedAddr.city || '';
                zipInput.value = selectedAddr.zip_code || '';
                phoneInput.value = selectedAddr.phone_number || '';
                // Fields are not required when using a saved address ID
                addressInput.required = false;
                cityInput.required = false;
                zipInput.required = false;
                phoneInput.required = false;
            }
        }
    }

    // Attach event listener to all address radio buttons
    document.querySelectorAll('input[name="saved_address"]').forEach(radio => {
        radio.addEventListener('change', (e) => manageAddressFields(e.target.value));
    });

    // Run on page load to set initial state correctly
    const initiallyCheckedAddress = document.querySelector('input[name="saved_address"]:checked');
    if (initiallyCheckedAddress) {
        manageAddressFields(initiallyCheckedAddress.value);
    }

    // --- Show/hide card details ---
    const cardOptions = document.getElementById('card-options');
    const newCardForm = document.getElementById('new-card-form');
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const cardChoiceRadios = document.querySelectorAll('input[name="card_choice"]');

    function togglePaymentView() {
        if (document.getElementById('pay-card').checked) {
            cardOptions.style.display = 'block';
            toggleNewCardForm();
        } else {
            cardOptions.style.display = 'none';
            document.querySelectorAll('#new-card-form input').forEach(i => i.required = false);
        }
    }

    function toggleNewCardForm() {
        const useNewCard = document.querySelector('input[name="card_choice"][value="new"]').checked;
        const newCardInputs = document.querySelectorAll('#new-card-form input');
        if (useNewCard && document.getElementById('pay-card').checked) {
            newCardForm.style.display = 'block';
            newCardInputs.forEach(i => { if (i.type !== 'checkbox') i.required = true; });
        } else {
            newCardForm.style.display = 'none';
            newCardInputs.forEach(i => i.required = false);
        }
    }

    paymentRadios.forEach(r => r.addEventListener('change', togglePaymentView));
    cardChoiceRadios.forEach(r => r.addEventListener('change', toggleNewCardForm));
    togglePaymentView();

    // --- Delete Saved Items Logic with Custom Modal ---
    const modal = document.getElementById('delete-modal');
    const modalContent = document.getElementById('delete-modal-content');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    const modalText = document.getElementById('delete-modal-text');
    let itemToDelete = null;

    function showModal(id, type, element) {
        itemToDelete = { id, type, element };
        modalText.textContent = `Are you sure you want to delete this saved ${type}?`;
        modal.classList.remove('hidden');
        setTimeout(() => {
             modal.style.opacity = '1';
             modalContent.style.opacity = '1';
             modalContent.style.transform = 'scale(1)';
        }, 10);
        feather.replace();
    }

    function hideModal() {
        modal.style.opacity = '0';
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.querySelectorAll('.delete-item-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const id = button.dataset.id;
            const type = button.dataset.type;
            const itemLabel = button.closest('.saved-item-label');
            showModal(id, type, itemLabel);
        });
    });

    // FIXED: Prevent delete button from interfering with card selection
    document.querySelectorAll('.saved-item-label').forEach(label => {
        label.addEventListener('click', (e) => {
            // Only select the address/card if the click wasn't on the delete button
            if (!e.target.closest('.delete-item-btn')) {
                const radio = label.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    });

    cancelBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

    confirmBtn.addEventListener('click', async () => {
        if (!itemToDelete) return;
        const { id, type, element } = itemToDelete;

        try {
            const response = await fetch('delete_saved_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, type })
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.error || 'Failed to delete.');

            hideModal();
            element.style.transition = 'opacity 0.3s, transform 0.3s, margin 0.3s, padding 0.3s, height 0.3s';
            element.style.opacity = '0';
            element.style.transform = 'scale(0.95)';
            element.style.marginTop = '0';
            element.style.marginBottom = '0';
            element.style.paddingTop = '0';
            element.style.paddingBottom = '0';
            element.style.height = '0px';
            element.style.borderWidth = '0px';

            setTimeout(() => element.remove(), 350);

        } catch (error) {
            hideModal();
            alert(`Error: ${error.message}`);
        } finally {
            itemToDelete = null;
        }
    });
});
</script>
<?php include 'footer.php'; ?>
[file content end]