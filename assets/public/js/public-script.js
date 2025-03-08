(function ($) {
  $(document).ready(function () {
    // Use event delegation to target dynamically loaded elements
    $(document).on("click", ".wc-block-cart__submit-button", function (e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: wpb_public_localize.ajax_url,
        data: {
          action: "proceed_to_checkout",
        },
        success: function (response) {
          if (response.success) {
            window.location.href = response.data.checkout_url;
          } else {
            alert("Failed to proceed to checkout.");
          }
        },
      });
    });
  });
})(jQuery);
