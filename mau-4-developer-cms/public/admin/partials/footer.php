      </main>
    </div>
  </div>

  <!-- Local Vendor JS: Bootstrap 5 Bundle, SweetAlert2, SortableJS -->
  <script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
  <script src="<?= asset('vendor/sortablejs/sortable.min.js') ?>"></script>

  <script>
    // Lấy CSRF Token từ meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Hàm thông báo Toast an toàn (có fallback console nếu Swal không tải được)
    function showNotify(type, message) {
      if (typeof Swal !== 'undefined' && typeof Swal.mixin === 'function') {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          background: '#1B222C',
          color: '#E6EDF3',
          didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
          }
        });
        Toast.fire({ icon: type, title: message });
      } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
      }
    }

    // 1. Tự động kích hoạt SortableJS cho các bảng có thuộc tính [data-sortable-table]
    if (typeof Sortable !== 'undefined') {
      document.querySelectorAll('[data-sortable-table]').forEach(container => {
        const tableName = container.getAttribute('data-sortable-table');
        const handleClass = container.getAttribute('data-sortable-handle') || '.drag-handle';

        new Sortable(container, {
          handle: handleClass,
          animation: 150,
          ghostClass: 'sortable-ghost',
          onEnd: function () {
            const itemIds = Array.from(container.querySelectorAll('[data-id]')).map(el => el.getAttribute('data-id'));
            
            fetch('ajax/reorder.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({
                table: tableName,
                ids: itemIds
              })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                showNotify('success', data.message || 'Đã cập nhật thứ tự thành công!');
              } else {
                showNotify('error', data.message || 'Không thể cập nhật thứ tự');
              }
            })
            .catch(() => {
              showNotify('error', 'Lỗi kết nối máy chủ');
            });
          }
        });
      });
    }

    // 2. Xử lý Toggle Switch nhanh qua AJAX
    document.querySelectorAll('.ajax-toggle').forEach(toggle => {
      toggle.addEventListener('change', function () {
        const table = this.getAttribute('data-table');
        const id = this.getAttribute('data-id');
        const column = this.getAttribute('data-column') || 'is_active';
        const checkbox = this;

        fetch('ajax/toggle.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            table: table,
            id: id,
            column: column
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showNotify('success', data.message || 'Đã cập nhật trạng thái!');
          } else {
            checkbox.checked = !checkbox.checked; // Revert checkbox
            showNotify('error', data.message || 'Lỗi khi cập nhật trạng thái');
          }
        })
        .catch(() => {
          checkbox.checked = !checkbox.checked;
          showNotify('error', 'Lỗi kết nối máy chủ');
        });
      });
    });

    // 3. Confirm trước khi xóa (có fallback an toàn confirm() nếu SweetAlert2 không tải được)
    document.querySelectorAll('.form-delete').forEach(form => {
      form.addEventListener('submit', function (e) {
        const itemName = this.getAttribute('data-item-name') || 'mục này';

        // Fallback: nếu Swal chưa sẵn sàng, dùng confirm() trình duyệt và không làm hỏng nút submit
        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
          if (!confirm(`Bạn có chắc chắn muốn xóa "${itemName}" không? Thao tác này không thể hoàn tác!`)) {
            e.preventDefault();
          }
          return;
        }

        e.preventDefault();
        Swal.fire({
          title: 'Xác nhận xóa?',
          text: `Bạn có chắc chắn muốn xóa "${itemName}" không? Thao tác này không thể hoàn tác!`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Đồng ý xóa',
          cancelButtonText: 'Hủy bỏ',
          background: '#1B222C',
          color: '#E6EDF3'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });

    // 4. Preview ảnh trước khi lưu
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
      input.addEventListener('change', function () {
        const previewId = this.getAttribute('data-preview');
        const previewEl = document.getElementById(previewId);
        if (previewEl && this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function (e) {
            previewEl.src = e.target.result;
            previewEl.classList.remove('d-none');
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    });

    // 5. Tự động tắt thông báo sau 3.5 giây với hiệu ứng mượt
    document.querySelectorAll('.alert.alert-dismissible').forEach(alertEl => {
      setTimeout(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
          const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
          bsAlert.close();
        } else {
          alertEl.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          alertEl.style.opacity = '0';
          alertEl.style.transform = 'translateY(-10px)';
          setTimeout(() => alertEl.remove(), 400);
        }
      }, 3500);
    });
  </script>
</body>
</html>
