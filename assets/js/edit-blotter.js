function showEditStep(step) {
    editTabPanes.forEach((pane, index) => {
        if (index + 1 === step) {
            pane.classList.add('show', 'active');
        } else {
            pane.classList.remove('show', 'active');
        }
    });
    editCurrentStep = step - 1;
    updateEditStepIndicator();
    updateEditFooterButtons();
}
