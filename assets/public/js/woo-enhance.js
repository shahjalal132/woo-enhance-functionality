jQuery(document).ready(function ($) {
  // start: add to cart functionality
  $("#custom-add-to-cart").on("click", function (e) {
    e.preventDefault();

    // get loader wrapper
    const loader_button = $(".add-to-cart-spinner-loader-wrapper");
    // add loading spinner
    loader_button.addClass("loader-spinner");

    let product_id = $(this).data("product-id");
    let custom_dropdown = {};

    $(".dropdown-group select").each(function () {
      let key = $(this).attr("name");
      let value = $(this).val();
      custom_dropdown[key] = value;
    });

    // get quantity
    let quantity = $("#wef-quantity").val();
    // get unit measurements
    let unit_measurements = $("#unit_measurements").val();

    // if quantity is empty return
    if (!quantity) {
      // remove loading spinner
      loader_button.removeClass("loader-spinner");
      // alert quantity is empty
      alert("Quantity is empty");
      return;
    }

    $.ajax({
      type: "POST",
      url: wooEnhanceParams.ajax_url,
      data: {
        action: "custom_add_to_cart",
        product_id: product_id,
        custom_dropdown: custom_dropdown,
        unit_measurements: unit_measurements,
        quantity: quantity,
      },
      success: function (response) {
        // remove loading spinner
        loader_button.removeClass("loader-spinner");
        if (response.success) {
          window.location.href = wooEnhanceParams.cart_url;
        } else {
          alert("Failed to add to cart.");
        }
      },
    });
  });
  //   end: add to cart functionality
});
