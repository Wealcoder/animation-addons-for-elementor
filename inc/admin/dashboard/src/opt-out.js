document.addEventListener("DOMContentLoaded", function () {
    // Find the row of the plugin using the slug
    var pluginRow = document.querySelector('tr[data-slug="animation-addons-for-elementor"]');

    if (!pluginRow) return;

    // Find the "Deactivate" link inside the row
    var deactivateLink = pluginRow.querySelector('.deactivate a');

    if (!deactivateLink) return;

    // Add click event listener
    deactivateLink.addEventListener("click", function (event) {
        event.preventDefault(); // Stop default action

        var userConfirmed = confirm(
            "Are you sure you want to deactivate? Please provide feedback."
        );

        if (userConfirmed) {
            window.location.href = deactivateLink.href; // Proceed with deactivation
        }
    });
});
