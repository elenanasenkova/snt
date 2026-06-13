<?php

require_once BASE_PATH . '/src/helpers/csrf.php';

use models\User;
use models\Fee;
use models\Finance;
use models\Meeting;
use models\Registration;
use models\Announcement;

/**
 * Ядро админ-панели: сводный дашборд, управление пользователями и модерация
 * заявок на регистрацию. Финансы, голосования/собрания и канцелярия вынесены
 * в AdminFinanceController, AdminVoteController, AdminOfficeController.
 */
class AdminController
{
    public static function dashboard(): void
    {
        requireRole([1, 2, 3, 4]);

        $users               = User::all();
        $meetings            = Meeting::upcoming();
        $year                = (int)date('Y');
        $feeTypes            = Fee::typesByYear($year);
        $pendingRegistrations = Registration::pending();
        $stats = [
            'total_users'       => count($users),
            'active_members'    => count(array_filter($users, fn($u) => $u['status'] === 'active')),
            'pending_reg'       => count($pendingRegistrations),
            'upcoming_meetings' => count($meetings),
        ];

        $summary = Finance::summary($year);

        // Должники: участки с неоплаченными обязательными взносами
        $db = \services\DatabaseConnection::get();
        $debtStmt = $db->prepare("SELECT COUNT(DISTINCT fp.plot_number) as cnt, COALESCE(SUM(fp.required_amount - fp.paid_amount),0) as total FROM fee_payments fp WHERE fp.status != 'paid' AND fp.required_amount > 0");
        $debtStmt->execute();
        $debtRow = $debtStmt->get_result()->fetch_assoc();
        $debtStmt->close();
        $debtors = ['count' => (int)($debtRow['cnt'] ?? 0), 'total' => (float)($debtRow['total'] ?? 0)];

        $announcements = Announcement::all(1, 5);
        renderTemplate('admin/dashboard', compact('users', 'meetings', 'feeTypes', 'pendingRegistrations', 'stats', 'year', 'announcements', 'summary', 'debtors'));
    }

    /**
     * Пользователи = единый реестр членов СНТ (ФЗ-217): просмотр, полное
     * редактирование и выгрузка в Excel. Контактные данные правит всё правление
     * (роли 1, 2, 4 — включая секретаря); роль и статус — только админ/председатель.
     * Объединяет бывшие «Реестр» и «Пользователи».
     */
    public static function users(): void
    {
        requireRole([1, 2, 4]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/users');
                return;
            }
            $action = $_POST['action'] ?? '';
            $uid    = (int)($_POST['user_id'] ?? 0);

            if ($action === 'update' && $uid > 0) {
                User::update(
                    $uid,
                    trim($_POST['full_name'] ?? ''),
                    trim($_POST['phone'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    trim($_POST['address'] ?? '')
                );
                // Роль и статус вправе менять только админ/председатель
                if (isAdmin()) {
                    if (isset($_POST['role_id'])) User::updateRole($uid, (int)$_POST['role_id']);
                    if (isset($_POST['status']))  User::updateStatus($uid, $_POST['status']);
                }
                flash('Данные участника обновлены', 'success');
                redirect('/admin/users');
            }
        }

        $users = User::all();
        $roles = User::roles();

        renderTemplate('admin/users', compact('users', 'roles') + ['flash' => getFlash()]);
    }

    /** Выгрузка реестра участников в CSV (открывается в Excel). Доступ: правление (1, 2, 4). */
    public static function usersExport(): void
    {
        requireRole([1, 2, 4]);
        $users = User::all();
        $statusRu = ['active' => 'Активен', 'inactive' => 'Неактивен', 'pending' => 'Ожидает'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reestr_snt_berezka.csv"');
        echo "\xEF\xBB\xBF"; // BOM для корректной кириллицы в Excel

        $out = fopen('php://output', 'w');
        fputcsv($out, ['№ участка', 'ФИО', 'Телефон', 'Email', 'Роль', 'Статус', 'Дата вступления'], ';');
        foreach ($users as $u) {
            fputcsv($out, [
                plotFromAddress($u['address'] ?? ''),
                $u['full_name'] ?? '',
                $u['phone'] ?? '',
                $u['email'] ?? '',
                $u['role_name'] ?? '',
                $statusRu[$u['status'] ?? ''] ?? ($u['status'] ?? ''),
                !empty($u['created_at']) ? date('d.m.Y', strtotime($u['created_at'])) : '',
            ], ';');
        }
        fclose($out);
    }

    public static function moderation(): void
    {
        requireRole([1, 2]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/moderation');
                return;
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'approve') {
                Registration::approve((int)$_POST['id'], getCurrentUser()['id']);
                flash('Заявка одобрена', 'success');
                redirect('/admin/moderation');
            }

            if ($action === 'reject') {
                Registration::reject((int)$_POST['id'], getCurrentUser()['id']);
                flash('Заявка отклонена', 'success');
                redirect('/admin/moderation');
            }
        }

        $registrations = Registration::pending();

        renderTemplate('admin/moderation', compact('registrations') + ['flash' => getFlash()]);
    }
}
