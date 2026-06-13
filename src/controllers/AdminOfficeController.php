<?php

require_once BASE_PATH . '/src/helpers/csrf.php';

/**
 * Канцелярия админ-панели: архив документов и обращения в правление.
 * Выделено из AdminController. Реестр членов объединён со страницей
 * «Пользователи» (AdminController::users).
 */
class AdminOfficeController
{
    /** Архив документов СНТ: устав, протоколы, сметы и т.п. Доступ: админ, председатель, секретарь. */
    public static function documents(): void
    {
        requireRole([1, 2, 4]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/documents');
                return;
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'delete') {
                \models\Document::delete((int)($_POST['id'] ?? 0));
                flash('Документ удалён', 'success');
                redirect('/admin/documents');
                return;
            }

            if ($action === 'create') {
                $title    = trim($_POST['title'] ?? '');
                $category = $_POST['category'] ?? 'другое';
                $allowedCat = ['устав', 'протокол', 'положение', 'смета', 'другое'];
                if (!in_array($category, $allowedCat, true)) $category = 'другое';
                $isPublic = isset($_POST['is_public']);

                if ($title === '' || empty($_FILES['file']['tmp_name'])) {
                    flash('Укажите название и выберите файл', 'error');
                    redirect('/admin/documents');
                    return;
                }
                if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    flash('Ошибка загрузки файла', 'error');
                    redirect('/admin/documents');
                    return;
                }

                $origName = $_FILES['file']['name'];
                $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowed  = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','odt','ods'];
                if (!in_array($ext, $allowed, true)) {
                    flash('Недопустимый формат файла', 'error');
                    redirect('/admin/documents');
                    return;
                }
                if ($_FILES['file']['size'] > 20 * 1024 * 1024) {
                    flash('Файл слишком большой (максимум 20 МБ)', 'error');
                    redirect('/admin/documents');
                    return;
                }

                $uploadDir = BASE_PATH . '/public/uploads/documents/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $safeName = preg_replace('/[^a-z0-9_.-]/i', '_', pathinfo($origName, PATHINFO_FILENAME));
                $fileName = date('Ymd_His') . '_' . $safeName . '.' . $ext;

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fileName)) {
                    flash('Не удалось сохранить файл', 'error');
                    redirect('/admin/documents');
                    return;
                }

                \models\Document::create(
                    $title,
                    trim($_POST['description'] ?? ''),
                    $category,
                    $fileName,
                    (int)$_FILES['file']['size'],
                    $_FILES['file']['type'] ?? '',
                    $isPublic,
                    getCurrentUser()['id']
                );
                flash('Документ добавлен', 'success');
                redirect('/admin/documents');
                return;
            }
        }

        $documents = \models\Document::all();
        renderTemplate('admin/documents', compact('documents') + ['flash' => getFlash()]);
    }

    /**
     * Обращения членов в правление (роли 1, 2). GET — список/просмотр,
     * POST — ответ (с уведомлением автору) или смена статуса.
     */
    public static function tickets(): void
    {
        requireRole([1, 2]);
        \models\Ticket::ensureTable();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/tickets');
                return;
            }
            $action   = $_POST['action'] ?? '';
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $ticket   = \models\Ticket::find($ticketId);
            if (!$ticket) {
                flash('Обращение не найдено', 'error');
                redirect('/admin/tickets');
                return;
            }

            if ($action === 'reply') {
                $body = trim($_POST['body'] ?? '');
                if ($body === '') {
                    flash('Введите текст ответа', 'error');
                    redirect('/admin/tickets?id=' . $ticketId);
                    return;
                }
                // Ответ правления переводит обращение в «Отвечено»
                \models\Ticket::addReply($ticketId, getCurrentUser()['id'], $body, 'answered');
                \models\Notification::create(
                    (int)$ticket['user_id'],
                    'info',
                    'Ответ на обращение № ' . $ticketId,
                    'Правление ответило на ваше обращение «' . $ticket['subject'] . '».',
                    '/tickets?id=' . $ticketId
                );
                flash('Ответ отправлен, член уведомлён', 'success');
                redirect('/admin/tickets?id=' . $ticketId);
                return;
            }

            if ($action === 'status') {
                $status = $_POST['status'] ?? '';
                if (!in_array($status, \models\Ticket::STATUSES, true)) {
                    flash('Неизвестный статус', 'error');
                    redirect('/admin/tickets?id=' . $ticketId);
                    return;
                }
                \models\Ticket::setStatus($ticketId, $status);
                \models\Notification::create(
                    (int)$ticket['user_id'],
                    'info',
                    'Статус обращения № ' . $ticketId . ' изменён',
                    'Новый статус: ' . (\models\Ticket::STATUS_LABELS[$status] ?? $status) . '.',
                    '/tickets?id=' . $ticketId
                );
                flash('Статус обновлён', 'success');
                redirect('/admin/tickets?id=' . $ticketId);
                return;
            }

            redirect('/admin/tickets');
            return;
        }

        $filter = $_GET['status'] ?? '';
        if (!in_array($filter, \models\Ticket::STATUSES, true)) $filter = '';

        $viewId  = (int)($_GET['id'] ?? 0);
        $ticket  = null;
        $replies = [];
        if ($viewId > 0) {
            $ticket = \models\Ticket::find($viewId);
            if ($ticket) {
                $replies = \models\Ticket::replies($viewId);
            }
        }

        $tickets = \models\Ticket::all($filter);
        renderTemplate('admin/tickets', compact('tickets', 'ticket', 'replies', 'filter') + ['flash' => getFlash()]);
    }
}
