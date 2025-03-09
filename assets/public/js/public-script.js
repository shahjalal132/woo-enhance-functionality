(function ($) {
  $(document).ready(function () {
    // start: proceed to checkout process
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
    // end: proceed to checkout process

    // start: handle height dropdown change
    $(document).on(
      "change",
      "select[name='custom_dropdown[height]']",
      function () {
        // get the price selector
        let priceToReplace = $(".selected-price");
        // get replace to formatted height selector
        let formattedHeightSelector = $(".replace-to-formatted-height");

        // get the selected value
        let selectedPrice = $(this).val();
        // update the price with selected value
        priceToReplace.text(
          `${selectedPrice} ${wpb_public_localize.currency_symbol}`
        );

        // get current product id
        let productId = $("#current_product_id").val();

        // hide dropdown-not-selected-state
        $(".dropdown-not-selected-state").hide();
        // display dropdown-selected-state
        $(".dropdown-selected-state").show();

        // get dropdown selected label
        let selectedLabel = $(this).find("option:selected").text();
        // trim the label
        selectedLabel = selectedLabel.trim();
        // split with space
        let splitLabel = selectedLabel.split(" ");
        // get the formatted height
        let formattedHeight = splitLabel[0];
        // update the formatted height
        formattedHeightSelector.text(formattedHeight);

        // send ajax call to save the height values
        $.ajax({
          type: "POST",
          url: wpb_public_localize.ajax_url,
          data: {
            action: "handle_save_height_dropdown_value",
            productId: productId,
            selectedPrice: selectedPrice,
            selectedLabel: selectedLabel,
          },
          success: function (response) {
            // console.log(response);
          },
        });
      }
    );
    // end: handle height dropdown change
  });
})(jQuery);
