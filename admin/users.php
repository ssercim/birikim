<?php
// Bu dosya admin/index.php tarafından dahil edildiği için
// kendi HTML, HEAD, BODY etiketlerine ve config.php dahil etmeye ihtiyacı yoktur.
// PDO bağlantısı ($pdo) ve formatlama fonksiyonları (formatPhoneNumber, formatDate, formatCurrency)
// admin/config.php üzerinden global olarak erişilebilir.

// Tüm kullanıcıları çekme sorgusu
$users = [];
try {
    // $pdo objesi admin/index.php'den gelmektedir.
    $stmt = $pdo->query("SELECT id, name, email, phone_number, profile_image_path FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Hata durumunda mesajı göster
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>";
    echo "<strong class='font-bold'>Hata!</strong>";
    echo "<span class='block sm:inline'> Kullanıcılar çekilirken bir sorun oluştu: " . $e->getMessage() . "</span>";
    echo "</div>";
}
?>

<h2 class="text-3xl font-bold text-gray-800 mb-6">Kullanıcı Yönetimi</h2>

<div class="flex justify-end mb-4">
    <button class="action-button add-button">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Yeni Kullanıcı Ekle
    </button>
</div>

<?php if (!empty($users)): ?>
    <div class="overflow-x-auto rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profil</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adı</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-posta</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefon</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Profil">
                            <img src="<?php echo htmlspecialchars($user['profile_image_path'] ?: 'https://placehold.co/40x40/a78bfa/ffffff?text=PP'); ?>"
                                 alt="Profil Resmi" class="profile-image-thumb"
                                 onerror="this.onerror=null;this.src='https://placehold.co/40x40/a78bfa/ffffff?text=PP';">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Adı"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="E-posta"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Telefon"><?php echo htmlspecialchars(formatPhoneNumber($user['phone_number'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="İşlemler">
                            <div class="action-buttons-group">
                                <button class="action-button edit-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.38-2.828-2.829z" />
                                    </svg>
                                    Düzenle
                                </button>
                                <button class="action-button delete-button ml-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 000-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm-2 4a1 1 0 100 2h4a1 1 0 100-2H5z" clip-rule="evenodd" />
                                    </svg>
                                    Sil
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-600 text-center mt-8">Henüz hiç kullanıcı bulunamadı.</p>
<?php endif; ?>

<style>
    /* users.php'ye özel stil tanımları (index.php'den taşındı) */
    .profile-image-thumb {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #6366f1;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
    }
    th, td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    th {
        background-color: #f8fafc;
        color: #4b5563;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
    }
    tr:hover {
        background-color: #f0f4f8;
    }
    .action-button {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: background-color 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .add-button {
        background-color: #4CAF50; /* Yeşil */
        color: white;
    }
    .add-button:hover {
        background-color: #45a049;
    }
    .edit-button {
        background-color: #2196F3; /* Mavi */
        color: white;
    }
    .edit-button:hover {
        background-color: #0b7dda;
    }
    .delete-button {
        background-color: #f44336; /* Kırmızı */
        color: white;
    }
    .delete-button:hover {
        background-color: #da190b;
    }
    @media (max-width: 768px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        tr {
            border: 1px solid #e2e8f0;
            margin-bottom: 0.75rem;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        td {
            border: none;
            position: relative;
            padding-left: 50%;
            text-align: right;
        }
        td:before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            width: 45%;
            padding-left: 1rem;
            font-weight: 600;
            color: #4b5563;
            text-align: left;
        }
        td:last-child {
            text-align: center;
        }
        .action-buttons-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
    }
</style>
