(function () {
    var hideAfterMs = 30000;

    function dismissBootstrapAlert(alert) {
        if (window.bootstrap && window.bootstrap.Alert) {
            window.bootstrap.Alert.getOrCreateInstance(alert).close();
            return;
        }

        alert.remove();
    }

    function dismissAdminToast(toast) {
        toast.classList.add('admin-toast-hiding');

        window.setTimeout(function () {
            toast.remove();
        }, 250);
    }

    window.setTimeout(function () {
        document.querySelectorAll('.alert.alert-dismissible').forEach(dismissBootstrapAlert);
        document.querySelectorAll('.admin-toast').forEach(dismissAdminToast);
    }, hideAfterMs);
})();
