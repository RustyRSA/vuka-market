// Vuka Market - client-side enhancements (vanilla JS + Bootstrap)
document.addEventListener('DOMContentLoaded', function () {

  // 1. Confirm before releasing escrow to the seller
  document.querySelectorAll('form').forEach(function (form) {
    var action = form.querySelector('input[name="action"]');
    if (action && action.value === 'confirm') {
      form.addEventListener('submit', function (e) {
        if (!confirm('Confirm you have received the item? This releases your payment to the seller.')) {
          e.preventDefault();
        }
      });
    }
  });

  // 2. Live client-side price validation on the Sell form
  var price = document.querySelector('input[name="price"]');
  if (price) {
    price.addEventListener('input', function () {
      var ok = parseFloat(price.value) > 0;
      price.classList.toggle('is-invalid', !ok);
    });
  }

  // 3. Auto-dismiss success alerts after 4 seconds
  document.querySelectorAll('.alert-success').forEach(function (a) {
    setTimeout(function () { a.classList.remove('show'); }, 4000);
  });
});
