</main>

<div class="owner-confirm-overlay" id="ownerConfirmOverlay">
    <div class="owner-confirm-modal">
        <div class="owner-confirm-modal__icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <p class="owner-confirm-modal__message" id="ownerConfirmMessage"></p>
        <div class="owner-confirm-modal__actions">
            <button type="button" class="owner-btn" id="ownerConfirmCancel">Batal</button>
            <button type="button" class="owner-btn owner-btn--primary" id="ownerConfirmOk">Ya, Lanjutkan</button>
        </div>
    </div>
</div>
<script src="<?php echo htmlspecialchars(ownerAssetUrl('assets/js/owner.js')); ?>"></script>
</body>
</html>
