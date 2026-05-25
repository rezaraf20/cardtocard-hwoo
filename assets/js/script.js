function htCopy(id){
    let text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text);
}
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('htReceiptForm');
    if(!form) return;
    const input = document.getElementById('htReceiptInput');
    const preview = document.getElementById('htPreviewImage');
    const previewWrapper = document.getElementById('htPreviewWrapper');
    const message = document.getElementById('htUploadMessage');
    const submitBtn = form.querySelector('button');
    input.addEventListener('change', function(){
        const file = this.files[0];
        if(!file) return;
        if(file.type.includes('image')){
            const reader = new FileReader();
            reader.onload = function(e){
                preview.src = e.target.result;
                previewWrapper.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
    form.addEventListener('submit', function(e){
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerText = 'در حال ارسال...';
        const formData = new FormData(form);
        fetch(ht_ajax_obj.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            submitBtn.disabled = false;
            submitBtn.innerText = 'ثبت رسید پرداخت';
            if(res.success){
                message.innerHTML =
                    '<div class="ht-success">' +
                    'با تشکر از پرداخت شما،<br>' +
                    'در اسرع وقت پس از بررسی، پردازش سفارش شما انجام خواهد شد.' +
                    '</div>';
                form.reset();
            }else{
                message.innerHTML =
                    '<div class="ht-error">' +
                    res.data +
                    '</div>';
            }
        });
    });
});
