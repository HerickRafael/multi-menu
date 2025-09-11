<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Company.php';

class AdminSettingsController extends Controller {
  private function guard($slug) {
    Auth::start();
    $u = Auth::user();
    if (!$u) {
      header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
      exit;
    }
    $company = Company::findBySlug($slug);
    if (!$company) { echo "Empresa inválida"; exit; }
    if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) { echo "Acesso negado"; exit; }
    return [$u,$company];
  }

  /**
   * Faz upload de imagem e retorna caminho relativo (ex.: "uploads/arquivo.jpg").
   * Se não houver arquivo, retorna null sem erro.
   * Em caso de erro, preenche $error e retorna null.
   */
  private function handleUpload(?array $file, string $prefix, ?string &$error = null): ?string {
    $error = null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($file['error'] !== UPLOAD_ERR_OK) {
      $error = 'Erro no upload (código ' . $file['error'] . ')';
      error_log($error . ' para ' . ($file['tmp_name'] ?? 'temp'));
      return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
      $error = 'Formato de arquivo inválido. Use JPG, PNG ou WEBP.';
      return null;
    }

    $name = $prefix . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $dest = __DIR__ . '/../../public/uploads/' . $name;
    $dir  = dirname($dest);

    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
      $error = 'Falha ao criar diretório de upload';
      error_log($error . ': ' . $dir);
      return null;
    }

    if (!is_writable($dir)) {
      $error = 'Diretório de upload não gravável';
      error_log($error . ': ' . $dir);
      return null;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
      $error = 'Arquivo temporário inexistente';
      error_log($error . ': ' . ($file['tmp_name'] ?? ''));
      return null;
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      $error = 'Falha ao salvar o arquivo enviado.';
      $lastError = error_get_last();
      error_log("move_uploaded_file falhou: {$file['tmp_name']} -> {$dest} - " . ($lastError['message'] ?? 'sem detalhes'));
      return null;
    }

    return 'uploads/' . $name;
  }

  private function loadHours(int $companyId): array {
    $st = db()->prepare("SELECT * FROM company_hours WHERE company_id=? ORDER BY weekday");
    $st->execute([$companyId]);
    $rows = $st->fetchAll();
    if (!$rows) {
      for ($d=1;$d<=7;$d++){
        db()->prepare("INSERT INTO company_hours (company_id, weekday, is_open) VALUES (?,?,0)")
           ->execute([$companyId,$d]);
      }
      $st->execute([$companyId]);
      $rows = $st->fetchAll();
    }
    $by=[]; foreach($rows as $r){ $by[(int)$r['weekday']]=$r; }
    return $by;
  }

  public function index($params){
    [$u,$company] = $this->guard($params['slug']);
    $error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);
    $hours = $this->loadHours((int)$company['id']);
    return $this->view('admin/settings/index', compact('company','hours','error'));
  }

  private function normalizeWhatsapp(string $raw): string {
    $digits = preg_replace('/\D+/', '', $raw);
    $digits = substr($digits, 0, 15);
    if ($digits !== '' && strlen($digits) <= 11 && strpos($digits, '55') !== 0) {
      $digits = '55' . $digits;
    }
    return $digits;
  }

  private function parseTime(?string $t): ?string {
    $t = trim((string)$t);
    if ($t==='') return null;
    $t = str_replace('.', ':', $t);
    if (preg_match('/^\d{1,2}:\d{2}$/', $t)) return $t . ':00';
    if (preg_match('/^\d{3,4}$/', $t)) return sprintf('%02d:%02d:00', substr($t,0,-2), substr($t,-2));
    if (preg_match('/^\d{1,2}$/', $t)) return sprintf('%02d:00:00', (int)$t);
    if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $t)) return $t;
    return null;
  }

  public function save($params){
    [$u,$company] = $this->guard($params['slug']);

    // ----- Campos gerais
    $name      = trim($_POST['name'] ?? $company['name']);
    $whatsapp  = $this->normalizeWhatsapp($_POST['whatsapp'] ?? $company['whatsapp'] ?? '');
    $address   = trim($_POST['address'] ?? $company['address']);
    $highlight = trim($_POST['highlight_text'] ?? $company['highlight_text']);
    $min_order = ($_POST['min_order'] === '' ? null : (float)$_POST['min_order']);

    // Tempo médio (inteiros ou NULL)
    $avg_from = (isset($_POST['avg_delivery_min_from']) && $_POST['avg_delivery_min_from'] !== '')
                ? (int)$_POST['avg_delivery_min_from'] : null;
    $avg_to   = (isset($_POST['avg_delivery_min_to'])   && $_POST['avg_delivery_min_to']   !== '')
                ? (int)$_POST['avg_delivery_min_to']   : null;

    // Uploads
    $logoError = $bannerError = null;
    $newLogoPath   = $this->handleUpload($_FILES['logo']   ?? null, 'logo',   $logoError);
    $newBannerPath = $this->handleUpload($_FILES['banner'] ?? null, 'banner', $bannerError);
    $errMsgs = [];
    if ($logoError)   $errMsgs[] = $logoError;
    if ($bannerError) $errMsgs[] = $bannerError;
    if ($errMsgs)     $_SESSION['flash_error'] = implode(' ', $errMsgs);

    // ----- UPDATE companies
    $set  = "name=?, whatsapp=?, address=?, highlight_text=?, min_order=?, avg_delivery_min_from=?, avg_delivery_min_to=?";
    $vals = [$name, $whatsapp, $address, $highlight, $min_order, $avg_from, $avg_to];

    if ($newLogoPath)   { $set .= ", logo=?";   $vals[] = $newLogoPath; }
    if ($newBannerPath) { $set .= ", banner=?"; $vals[] = $newBannerPath; }

    $set .= " WHERE id=?";
    $vals[] = $company['id'];

    $sql = "UPDATE companies SET $set";
    db()->prepare($sql)->execute($vals);

    // ----- Horários por dia
    for ($d=1;$d<=7;$d++){
      $isOpen = isset($_POST['is_open'][$d]) ? 1 : 0;
      $o1 = $this->parseTime($_POST['open1'][$d] ?? null);
      $c1 = $this->parseTime($_POST['close1'][$d] ?? null);
      $o2 = $this->parseTime($_POST['open2'][$d] ?? null);
      $c2 = $this->parseTime($_POST['close2'][$d] ?? null);

      if (!$isOpen) { $o1=$c1=$o2=$c2=null; }

      db()->prepare("UPDATE company_hours SET is_open=?, open1=?, close1=?, open2=?, close2=? WHERE company_id=? AND weekday=?")
        ->execute([$isOpen, $o1, $c1, $o2, $c2, $company['id'], $d]);
    }

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/settings'));
  }
}
