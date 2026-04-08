<?php
require_once 'includes/config.php';

// Mulai session dan cek apakah user sudah login
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Sisipkan file koneksi database
require_once 'includes/db.php';

// Ambil semua data riwayat dari database, urutkan berdasarkan yang terbaru
$riwayat_result = $koneksi->query("SELECT * FROM bmi_history ORDER BY tanggal DESC, id DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Data Responden</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/admin.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Data Responden</h1>
            <div>
                <button id="delete-selected-btn" class="action-btn delete-btn" style="display: none; margin-right: 10px;">Hapus Terpilih</button>
                <a href="api/export_csv.php" class="action-btn export-btn">Export ke CSV</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="table-container">
            <table id="historyTable" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th data-orderable="false"><input type="checkbox" id="select-all"></th>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Usia</th>
                        <th>Gender</th>
                        <th>No. HP</th>
                        <th>BB (kg)</th>
                        <th>TB (cm)</th>
                        <th>Aktivitas</th>
                        <th>Kalori (kkal)</th>
                        <th>IMT</th>
                        <th>Status Gizi</th>
                        <th>Saran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($riwayat_result->num_rows > 0): ?>
                        <?php while($row = $riwayat_result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td><input type="checkbox" class="row-checkbox" value="<?= $row['id'] ?>"></td>
                            <td><?= htmlspecialchars(date("d-m-Y", strtotime($row['tanggal']))) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['usia']) ?> thn</td>
                            <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                            <td><?= htmlspecialchars($row['berat_badan']) ?></td>
                            <td><?= htmlspecialchars($row['tinggi_badan']) ?></td>
                            <td>
                                <?php
                                    $aktivitas_val = $row['aktivitas'];
                                    $aktivitas_level = '';
                                    if ($aktivitas_val == 1.2) $aktivitas_level = 'Sangat Ringan';
                                    elseif ($aktivitas_val == 1.375) $aktivitas_level = 'Ringan';
                                    elseif ($aktivitas_val == 1.55) $aktivitas_level = 'Sedang';
                                    elseif ($aktivitas_val == 1.725) $aktivitas_level = 'Berat';
                                    elseif ($aktivitas_val == 1.9) $aktivitas_level = 'Sangat Berat';
                                    else $aktivitas_level = htmlspecialchars($aktivitas_val);
                                    echo $aktivitas_level;
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['kalori']) ?></td>
                            <td><?= htmlspecialchars($row['imt']) ?></td>
                            <td>
                                <?php 
                                    // Menentukan kelas CSS yang benar berdasarkan status gizi dari database
                                    $status_gizi_db = $row['status_gizi'];
                                    $status_class = '';
                                    if (strpos($status_gizi_db, '(underweight)') !== false) {
                                        $status_class = 'underweight';
                                    } elseif (strpos($status_gizi_db, '(ideal)') !== false) {
                                        $status_class = 'normal';
                                    } elseif (strpos($status_gizi_db, '(overweight)') !== false) {
                                        $status_class = 'overweight';
                                    } elseif ($status_gizi_db == 'Obesitas') { // Obesitas doesn't have English in parentheses
                                        $status_class = 'obesitas';
                                    }
                                ?>
                                <span class="status <?= $status_class ?>">
                                    <?= htmlspecialchars($status_gizi_db) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['saran']) ?></td>
                            <td>
                                <button class="action-btn edit-btn" data-id="<?= $row['id'] ?>">Edit</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center;">Belum ada data responden.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <!-- Edit Modal HTML -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Data Responden</h2>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label for="edit_tanggal">Tanggal Input</label>
                            <input type="date" id="edit_tanggal" name="tanggal" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_nama">Nama Lengkap</label>
                            <input type="text" id="edit_nama" name="nama" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_usia">Usia (Tahun)</label>
                            <input type="number" id="edit_usia" name="usia" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_jenis_kelamin">Jenis Kelamin</label>
                            <select id="edit_jenis_kelamin" name="jenis_kelamin">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_no_hp">No. HP</label>
                            <input type="tel" id="edit_no_hp" name="no_hp" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_bb">Berat Badan (kg)</label>
                            <input type="number" step="0.1" id="edit_bb" name="berat_badan" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_tb">Tinggi Badan (cm)</label>
                            <input type="number" id="edit_tb" name="tinggi_badan" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_aktivitas">Aktivitas</label>
                            <select id="edit_aktivitas" name="aktivitas" required>
                                <option value="1.2">Sangat ringan (1.2)</option>
                                <option value="1.375">Ringan (1.375)</option>
                                <option value="1.55">Sedang (1.55)</option>
                                <option value="1.725">Berat (1.725)</option>
                                <option value="1.9">Sangat Berat (1.9)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_imt">IMT</label>
                            <input type="text" id="edit_imt" name="imt" readonly>
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_status_gizi">Status Gizi</label>
                            <input type="text" id="edit_status_gizi" name="status_gizi" readonly>
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_saran">Saran</label>
                            <textarea id="edit_saran" name="saran" rows="3" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; box-sizing: border-box;" readonly></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn">Batal</button>
                <button type="submit" form="editForm" class="save-btn">Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        function handleJsonResponse(response, successCallback, options = {}) {
            const defaultOptions = {
                showAlertOnSuccess: true,
                showAlertOnFailure: true
            };
            options = { ...defaultOptions, ...options }; // Merge default options

            var res;
            if (typeof response === 'object' && response !== null) {
                res = response;
            } else {
                try {
                    res = JSON.parse(response);
                } catch (e) {
                    alert('Error parsing server response: ' + response);
                    console.error('JSON Parse Error:', e);
                    return;
                }
            }

            if (res.success) {
                if (options.showAlertOnSuccess) { // Only show alert if flag is true
                    alert(res.message);
                }
                if (successCallback) successCallback(res);
            } else {
                if (options.showAlertOnFailure) { // Only show alert if flag is true
                    alert('Error: ' + res.message);
                }
            }
        }

        var table = $('#historyTable').DataTable({
            responsive: true,
            "columnDefs": [
                { "orderable": false, "targets": 0 } // Disable sorting on the checkbox column
            ],
            language: {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data yang cocok",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data tersedia",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first":      "Pertama",
                    "last":       "Terakhir",
                    "next":       "Berikutnya",
                    "previous":   "Sebelumnya"
                }
            }
        });

        // --- BULK DELETE LOGIC ---

        // Toggle "Delete Selected" button visibility
        function toggleDeleteButton() {
            var anyChecked = $('.row-checkbox:checked').length > 0;
            $('#delete-selected-btn').toggle(anyChecked);
        }

        // Handle "Select All" checkbox click
        $('#select-all').on('click', function() {
            // Check/uncheck all checkboxes in the table
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
            toggleDeleteButton();
        });

        // Handle individual row checkbox change
        $('#historyTable tbody').on('change', 'input[type="checkbox"]', function() {
            // If this checkbox is unchecked, uncheck "Select All"
            if (!this.checked) {
                var el = $('#select-all').get(0);
                if (el && el.checked && ('indeterminate' in el)) {
                    el.indeterminate = true;
                }
            }
            toggleDeleteButton();
        });
        
        // Handle table draw event to update select-all state
        table.on('draw', function() {
            // Uncheck "Select All" checkbox when table is redrawn (e.g., pagination)
            $('#select-all').prop('checked', false);
            toggleDeleteButton();
        });

        // Handle "Delete Selected" button click
        $('#delete-selected-btn').on('click', function() {
            var ids = [];
            // Get IDs from all checked checkboxes across all pages
            table.$('.row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                alert('Tidak ada data yang dipilih.');
                return;
            }

            if (confirm('Apakah Anda yakin ingin menghapus ' + ids.length + ' data yang dipilih?')) {
                $.ajax({
                    url: 'api/delete_record.php',
                    type: 'POST',
                    data: { ids: ids }, // Send as an array of IDs
                    success: function(response) {
                        handleJsonResponse(response, function(res) {
                            // Reload page to reflect changes
                            location.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan saat menghapus data: ' + error);
                        console.error('AJAX Error:', status, error, xhr);
                    }
                });
            }
        });

        // Handle Delete button click
        $('#historyTable tbody').on('click', '.delete-btn', function() {
            var button = $(this);
            var rowToDelete = button.closest('tr');
            var id = button.data('id');

            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                $.ajax({
                    url: 'api/delete_record.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        handleJsonResponse(response, function(res) {
                            // On success, remove the row from DataTables and redraw without resetting pagination
                            table.row(rowToDelete).remove().draw(false);
                        });
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan saat menghapus data: ' + error);
                        console.error('AJAX Error:', status, error, xhr);
                    }
                });
            }
        });

        // --- EDIT MODAL LOGIC ---
        var modal = $('#editModal');
        var span = $('.close-button');
        var saveBtn = $('.save-btn');
        var cancelBtn = $('.cancel-btn');

        // When the user clicks on <span> (x), close the modal
        span.on('click', function() {
            modal.hide();
        });

        // When the user clicks anywhere outside of the modal, close it
        $(window).on('click', function(event) {
            if (event.target == modal[0]) {
                modal.hide();
            }
        });

        // Handle Cancel button click
        cancelBtn.on('click', function() {
            modal.hide();
        });

        // Handle Edit button click
        $('#historyTable tbody').on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'api/get_record.php',
                type: 'GET',
                data: { id: id },
                                    success: function(response) {
                                                                handleJsonResponse(response, function(res) {
                                                                    // Populate the form fields
                                                                    $('#edit_id').val(res.data.id);
                                                                    $('#edit_tanggal').val(res.data.tanggal);
                                                                    $('#edit_nama').val(res.data.nama);
                                                                    $('#edit_usia').val(res.data.usia);
                                                                    $('#edit_jenis_kelamin').val(res.data.jenis_kelamin);
                                                                    $('#edit_no_hp').val(res.data.no_hp);
                                                                    $('#edit_bb').val(res.data.berat_badan);
                                                                    $('#edit_tb').val(res.data.tinggi_badan);
                                                                    $('#edit_aktivitas').val(res.data.aktivitas);
                                                                    $('#edit_imt').val(res.data.imt);
                                                                    $('#edit_status_gizi').val(res.data.status_gizi);
                                                                    $('#edit_saran').val(res.data.saran);
                                                                    
                                                                    modal.show(); // Display the modal
                                                                }, { showAlertOnSuccess: false }); // Do not show success alert for get_record                                    },                error: function(xhr, status, error) {
                    alert('Terjadi kesalahan saat mengambil data: ' + error);
                    console.error('AJAX Error:', status, error, xhr);
                }
            });
        });

        // Handle Save Changes button in modal
        $('#editForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            var formData = $(this).serialize(); // Get form data

            $.ajax({
                url: 'api/update_record.php',
                type: 'POST',
                data: formData,
                                    success: function(response) {
                                        handleJsonResponse(response, function(res) {
                                            modal.hide(); // Hide the modal
                                            location.reload(); // Reload the page to see changes
                                        });
                                    },                error: function(xhr, status, error) {
                    alert('Terjadi kesalahan saat menyimpan perubahan: ' + error);
                    console.error('AJAX Error:', status, error, xhr);
                }
            });
        });
    });
    </script>
    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>
</body>
</html>
