<script>
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 4000);
    });

    var slipBtns = document.querySelectorAll('.view-slip-btn');
    var modal = document.getElementById('slipModal');
    if (modal && slipBtns) {
        var slipImg = document.getElementById('slipImage');
        var closeBtn = modal.querySelector('.modal-close');
        
        slipBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var src = btn.getAttribute('data-src');
                slipImg.src = src;
                modal.classList.add('open');
            });
        });
        
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('open');
        });
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('open');
            }
        });
    }
});
</script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    if($.fn.DataTable) {
        $('.admin-table').DataTable({
            "order": [[0, "asc"]],
            "language": {
                "search": "ค้นหา:",
                "lengthMenu": "แสดง _MENU_ รายการ",
                "info": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "infoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ",
                "emptyTable": "ไม่มีข้อมูลในตาราง",
                "zeroRecords": "ไม่พบข้อมูลที่ค้นหา",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "สุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            }
        });
    }
});
</script>
</body>
</html>
