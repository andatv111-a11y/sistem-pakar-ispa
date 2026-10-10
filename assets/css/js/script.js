document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.confirm-delete').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm('Yakin ingin menghapus data ini?')) e.preventDefault();
        });
    });

    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => { el.style.transition='opacity .5s'; el.style.opacity='0'; setTimeout(()=>el.remove(),500); }, 4000);
    });
});