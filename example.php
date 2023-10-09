<!-- Berfungsi untuk mengambil data menu dari API -->
<?php
function fetchMenuData() {
    return json_decode(file_get_contents("http://tes-web.landa.id/intermediate/menu"), true);
}

// Berfungsi untuk mengambil data transaksi untuk tahun tertentu dari API
function fetchTransaksiData($tahun) {
    return json_decode(file_get_contents("http://tes-web.landa.id/intermediate/transaksi?tahun=" . $tahun), true);
}

// Berfungsi untuk menginisialisasi struktur data menu dengan nilai default
function initializeMenuData($menu) {
    $menuData = [];
    foreach ($menu as $menuItem) {
        $menuData[$menuItem['menu']] = [
            'menu' => $menuItem['menu'],
            'kategori' => $menuItem['kategori'],
            'value' => array_fill(0, 12, 0), // Inisialisasi array untuk menyimpan total per bulan
            'totalHarga' => 0, // Total harga awal untuk menu ini adalah 0
        ];
    }
    return $menuData;
}

// Berfungsi untuk menghitung total untuk item menu dan total keseluruhan
function calculateTotals($menuData, $transaksi) {
    $totalPerbulan = array_fill(0, 12, 0);
    $totalPertahun = 0;

    foreach ($transaksi as $transaction) {
        $harga = $transaction['total'];
        $tanggal = DateTime::createFromFormat("Y-m-d", $transaction['tanggal']);
        $bulan = $tanggal->format("n");
        $namaMenu = $transaction['menu'];

        // Memperbarui data menu untuk item menu tertentu
        if (isset($menuData[$namaMenu])) {
            $menuData[$namaMenu]['value'][$bulan - 1] += $harga;
            $menuData[$namaMenu]['totalHarga'] += $harga;
        }

        // Memperbarui total bulanan dan tahunan
        $totalPerbulan[$bulan - 1] += $harga;
        $totalPertahun += $harga;
    }

    return [$menuData, $totalPerbulan, $totalPertahun];
}

// Menentukan tahun yang tersedia dan mendapatkan tahun yang dipilih dari parameter kueri URL
$availableYears = ['2021', '2022'];
$selectedYear = isset($_GET['tahun']) && in_array($_GET['tahun'], $availableYears) ? $_GET['tahun'] : null;

if ($selectedYear) {
    // Mengambil dan menginisialisasi data menu, lalu menghitung total
    $menuData = initializeMenuData(fetchMenuData());
    list($menuData, $totalPerbulan, $totalPertahun) = calculateTotals($menuData, fetchTransaksiData($selectedYear));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabel Venturo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        td, th {
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="card" style="margin: 2rem 0rem;">
        <div class="card-header">
            Venturo - Laporan penjualan tahunan per menu
        </div>
        <div class="card-body">
            <form action="" method="get">
                <div class="row">
                    <div class="col-2">
                        <div class="form-group">
                            <select id="my-select" class="form-control" name="tahun">
                                <option value="">Pilih Tahun</option>
                                <?php foreach ($availableYears as $year): ?>
                                    <!-- Menghasilkan opsi dropdown untuk tahun yang tersedia -->
                                    <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </div>
            </form>
            <hr>
            <?php if ($selectedYear): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" style="margin: 0;">
                        <thead>
                            <tr class="table-dark">
                                <th rowspan="2" style="text-align:center;vertical-align: middle;width: 250px;">Menu</th>
                                <th colspan="12" style="text-align: center;">Periode Pada <?= $selectedYear ?></th>
                                <th rowspan="2" style="text-align:center;vertical-align: middle;width:75px">Total</th>
                            </tr>
                            <tr class="table-dark">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <!-- Membuat daftar bulan berdasarkan tahun yang dipilih -->
                                    <!-- mktime format ==> (jam, menit, detik, bulan, tanggal, tahun) -->
                                    <th style="text-align: center;width: 75px;"><?= date("M", mktime(0, 0, 0, $i, 1, $selectedYear)) ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $foodSeparatorAdded = false; // Untuk melacak apakah separator "Makanan" sudah ditambahkan.
                            $drinkSeparatorAdded = false; // Untuk melacak apakah separator "Minuman" sudah ditambahkan.

                            foreach ($menuData as $menu): ?>
                                <?php
                                if ($menu['kategori'] === "makanan"):
                                    // Tambahkan separator "Makanan" jika belum ditambahkan
                                    if (!$foodSeparatorAdded):
                                        ?>
                                        <tr>
                                            <td class="table-secondary" colspan="14"><b>Makanan</b></td>
                                        </tr>
                                        <?php
                                        $foodSeparatorAdded = true; // Tandai bahwa separator "Makanan" sudah ditambahkan
                                    endif;
                                elseif ($menu['kategori'] === "minuman"):
                                    // Tambahkan separator "Minuman" jika belum ditambahkan
                                    if (!$drinkSeparatorAdded):
                                        ?>
                                        <tr>
                                            <td class="table-secondary" colspan="14"><b>Minuman</b></td>
                                        </tr>
                                        <?php
                                        $drinkSeparatorAdded = true; // Tandai bahwa separator "Minuman" sudah ditambahkan
                                    endif;
                                endif;
                                ?>

                                <!-- Tampilkan baris menu -->
                                <tr>
                                    <td style="text-align: left;"><?= $menu['menu'] ?></td>
                                    <?php foreach ($menu['value'] as $value): ?>
                                        <td style="text-align: right;"><?= $value != 0 ? number_format($value) : "" ?></td>
                                    <?php endforeach; ?>
                                    <td style="text-align: right;"><b><?= number_format($menu['totalHarga']) ?></b></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr>
                                <td class="table-dark" colspan="1"><b>Total</b></td>
                                <?php foreach ($totalPerbulan as $total): ?>
                                    <td class="table-dark" style="text-align: right;"><b><?= number_format($total) ?></b></td>
                                <?php endforeach; ?>
                                <td class="table-dark" style="text-align: right;" colspan="1"><b><?= number_format($totalPertahun) ?></b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>