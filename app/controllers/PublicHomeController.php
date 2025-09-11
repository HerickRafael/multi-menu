<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

class PublicHomeController extends Controller
{
  private function loadHours(int $companyId): array {
    $st = db()->prepare("SELECT * FROM company_hours WHERE company_id=? ORDER BY weekday");
    $st->execute([$companyId]);
    $rows = $st->fetchAll();
    $by = [];
    foreach ($rows as $r) { $by[(int)$r['weekday']] = $r; }
    return $by;
  }

  private function openNow(array $todayRow): array {
    date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');
    $today    = new DateTime('today 00:00:00');
    $tomorrow = (clone $today)->modify('+1 day');
    $now      = new DateTime();

    $ranges = [];
    $mkRange = function(?string $o, ?string $c) use ($today, $tomorrow) {
      if (!$o || !$c) return null;
      $open  = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d').' '.$o);
      $close = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d').' '.$c);
      if (!$open || !$close) return null;
      if ($close < $open) { // vira à meia-noite
        $close = DateTime::createFromFormat('Y-m-d H:i:s', $tomorrow->format('Y-m-d').' '.$c);
      }
      return [$open, $close];
    };

    if (!empty($todayRow['is_open'])) {
      if ($r = $mkRange($todayRow['open1'] ?? null, $todayRow['close1'] ?? null)) $ranges[] = $r;
      if ($r = $mkRange($todayRow['open2'] ?? null, $todayRow['close2'] ?? null)) $ranges[] = $r;
    }

    $open = false;
    foreach ($ranges as [$a, $b]) { if ($now >= $a && $now <= $b) { $open = true; break; } }

    $label = 'Fechado hoje';
    if (!empty($todayRow['is_open']) && !empty($todayRow['open1']) && !empty($todayRow['close1'])) {
      $label = substr($todayRow['open1'],0,5).' - '.substr($todayRow['close1'],0,5);
      if (!empty($todayRow['open2']) && !empty($todayRow['close2'])) {
        $label .= ' / '.substr($todayRow['open2'],0,5).' - '.substr($todayRow['close2'],0,5);
      }
    }
    return [$open, $label];
  }

  /** HOME */
  public function index($params) {
    $slug = $params['slug'] ?? null;
    $q    = isset($_GET['q']) ? trim($_GET['q']) : '';

    $company = Company::findBySlug($slug);
    if (!$company || !$company['active']) { http_response_code(404); echo "Empresa não encontrada"; return; }

    date_default_timezone_set(config('timezone') ?? 'America/Sao_Paulo');

    $cid = (int)$company['id'];
    $db  = $this->db();

    $categories   = Category::listByCompany($cid);
    // Cardápio completo
    $products     = Product::listByCompany($cid);
    // Resultados da busca (se houver termo)
    $searchResults = ($q !== '') ? Product::listByCompany($cid, $q) : [];

    $hours  = $this->loadHours($cid);
    $w      = (int)date('N');
    $today  = $hours[$w] ?? ['is_open'=>0];
    [$isOpenNow, $todayLabel] = $this->openNow($today);

    // ===== Novidades & Mais pedidos =====
    $diasNovidade = (int)(config('novidades_days') ?? 14);
    $novidades    = Product::novidadesByCompanyId($db, $cid, $diasNovidade, 12);
    $maisPedidos  = Product::maisPedidosByCompanyId($db, $cid, 12);

    $mostraNovidade    = count($novidades) > 0;
    $mostraMaisPedidos = count($maisPedidos) > 0;
    $temAbas           = $mostraNovidade || $mostraMaisPedidos;
    $topMaisPedido     = $mostraMaisPedidos ? $maisPedidos[0] : null;

    return $this->view('public/home', compact(
      'company','categories','products','searchResults','q','hours','isOpenNow','todayLabel',
      'novidades','maisPedidos','topMaisPedido','mostraNovidade','mostraMaisPedidos','temAbas'
    ));
  }

  /** BUSCAR */
  public function buscar($params) {
    $slug = $params['slug'] ?? null;
    $q    = isset($_GET['q']) ? trim($_GET['q']) : '';

    $company = Company::findBySlug($slug);
    if (!$company || !$company['active']) { http_response_code(404); echo "Empresa não encontrada"; return; }

    $cid    = (int)$company['id'];
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
      // Retorna o HTML com os produtos para busca instantânea
      $products = Product::listByCompany($cid, $q);
      if (!function_exists('badgePromo')) {
        function badgePromo($p){ return !empty($p['promo_price']); }
      }
      ob_start();
      if ($q !== '') {
        echo '<h2 class="text-xl font-bold mb-2">Resultado da busca</h2><div class="grid gap-3">';
        if (!$products) {
          echo '<div class="p-4 border bg-white rounded-xl">Nada encontrado para <strong>'.e($q).'</strong>.</div>';
        }
        foreach ($products as $p) {
          include __DIR__ . '/../views/public/partials_card.php';
        }
        echo '</div>';
      }
      $html = ob_get_clean();
      header('Content-Type: text/html; charset=UTF-8');
      echo $html;
      return;
    }

    // Fallback: redireciona para a home com o termo de busca
    $url = base_url(rawurlencode($slug));
    if ($q !== '') {
      $url .= '?q=' . urlencode($q);
    }
    header('Location: ' . $url);
    exit;
  }
}
