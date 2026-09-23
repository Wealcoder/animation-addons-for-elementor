window.addEventListener("elementor/frontend/init", () => {
    const toggleSwitcher = ($scope) => {
        const checked = $scope.querySelectorAll("input");
        const togglePanes = $scope.querySelectorAll(".toggle-pane");
        const toggleLabels = $scope.querySelectorAll(".before_label, .after_label");

        checked.forEach((input) => {
            input.addEventListener("change", () => {
                togglePanes.forEach((pane) => pane.classList.toggle("show"));
                toggleLabels.forEach((label) => label.classList.toggle("active"));
            });
        });
    };

    elementorFrontend.hooks.addAction(
        "frontend/element_ready/wcf--toggle-switch.default",
        ($scope) => toggleSwitcher($scope[0] || $scope)
    );
});
