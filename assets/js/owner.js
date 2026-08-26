/**
 * Modal konfirmasi custom buat panel owner — pengganti confirm() bawaan
 * browser yang tampilannya "localhost says..." dan tidak konsisten dengan
 * desain aplikasi. Form yang butuh konfirmasi tinggal dikasih atribut
 * data-confirm="pesan..." (opsional data-confirm-danger buat aksi hapus/
 * merusak, biar tombolnya merah).
 */
(function () {
    var overlay = document.getElementById('ownerConfirmOverlay');
    if (!overlay) { return; }

    var modal = overlay.querySelector('.owner-confirm-modal');
    var messageEl = document.getElementById('ownerConfirmMessage');
    var okBtn = document.getElementById('ownerConfirmOk');
    var cancelBtn = document.getElementById('ownerConfirmCancel');
    var pendingForm = null;

    function closeModal() {
        overlay.classList.remove('active');
        pendingForm = null;
    }

    function openModal(form) {
        pendingForm = form;
        messageEl.textContent = form.getAttribute('data-confirm') || 'Yakin mau lanjutkan?';
        modal.classList.toggle('is-danger', form.hasAttribute('data-confirm-danger'));
        overlay.classList.add('active');
        okBtn.focus();
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) { return; }
        if (form.dataset.ownerConfirmed === '1') { return; }
        e.preventDefault();
        openModal(form);
    });

    okBtn.addEventListener('click', function () {
        if (!pendingForm) { return; }
        pendingForm.dataset.ownerConfirmed = '1';
        var form = pendingForm;
        closeModal();
        form.submit();
    });

    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { closeModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) { closeModal(); }
    });
})();
