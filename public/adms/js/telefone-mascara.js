document.addEventListener("DOMContentLoaded", function () {
    const phoneInput = document.getElementById("phone");

    if (phoneInput) {
        phoneInput.addEventListener("input", function () {
            let phone = phoneInput.value.replace(/\D/g, ""); // Remove tudo que não for número

            if (phone.length > 10) {
                phone = phone.replace(/^(\d{2})(\d{5})(\d{4})/, "($1) $2-$3"); // Formato (XX) XXXXX-XXXX
            } else if (phone.length > 6) {
                phone = phone.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3"); // Formato (XX) XXXX-XXXX
            } else if (phone.length > 2) {
                phone = phone.replace(/^(\d{2})(\d{0,5})/, "($1) $2"); // Início (XX) XXXX
            } else if (phone.length > 0) {
                phone = phone.replace(/^(\d{0,2})/, "($1"); // Início (XX
            }

            phoneInput.value = phone;
        });
    }
});