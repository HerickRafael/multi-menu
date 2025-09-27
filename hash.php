<?php
// bcrypt.php — Gerador/Validador Bcrypt ($2y$) em PHP puro
// Requisitos: PHP 7.2+ (funciona em versões mais novas também)

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$pwd      = $_POST['pwd']      ?? '';
$cost     = isset($_POST['cost']) ? (int)$_POST['cost'] : 10;
$action   = $_POST['action']   ?? '';
$hashOut  = '';
$msgGen   = '';
$msgVer   = '';

if ($action === 'gen') {
  $cost = max(4, min(31, $cost)); // limites seguros do bcrypt
  // PASSWORD_BCRYPT gera hash com prefixo $2y$ no PHP
  $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => $cost]);
  if ($hash === false) {
    $msgGen = '❌ Falha ao gerar o hash.';
  } else {
    $hashOut = $hash;
    $msgGen = '✅ Hash gerado com sucesso.';
  }
}

if ($action === 'check') {
  $hashIn = $_POST['hash_in'] ?? '';
  if ($pwd !== '' && $hashIn !== '') {
    // password_verify aceita $2y$, $2a$, etc.
    $ok = password_verify($pwd, $hashIn);
    $msgVer = $ok ? '✅ Senha CONFERE com o hash.' : '❌ Senha NÃO confere com o hash.';
  } else {
    $msgVer = 'Informe a senha e o hash para validar.';
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bcrypt ($2y$) — Gerar e Validar</title>
<style>
  :root{--bg:#0f1724;--card:#0b1220;--line:#1a2336;--ink:#e6eef6;--muted:#9fb0c7;--accent:#06b6d4}
  *{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial}
  body{margin:0;background:#0f1724;color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
  .card{width:100%;max-width:860px;background:#0b1220;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,.45)}
  h1{margin:0 0 10px;font-size:20px}
  p.lead{margin:0 0 16px;color:var(--muted);font-size:13px}
  label{display:block;margin:12px 0 6px;color:var(--muted);font-size:13px}
  input,select,textarea{width:100%;padding:11px;border-radius:10px;border:1px solid #22314f;background:#0e1628;color:var(--ink)}
  .row{display:flex;gap:12px;flex-wrap:wrap}
  .col{flex:1}
  button{background:var(--accent);color:#042024;border:0;padding:10px 14px;border-radius:10px;cursor:pointer;font-weight:700}
  .ghost{background:transparent;border:1px solid #22314f;color:var(--ink)}
  .result{margin-top:10px;padding:12px;background:#111c30;border-radius:10px;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
  .small{font-size:12px;color:var(--muted)}
  .ok{color:#9cffb0}.err{color:#ff9b9b}
  .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
</style>
</head>
<body>
  <main class="card" role="main">
    <h1>Bcrypt ($2y$) — Gerar e Validar</h1>
    <p class="lead">Gera hashes <strong>$2y$</strong> com custo configurável e valida senha vs. hash. Tudo processado no servidor PHP.</p>

    <!-- Gerar hash -->
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="gen">
      <label>Senha (plaintext)</label>
      <input name="pwd" type="password" placeholder="Digite a senha" required>

      <div class="row">
        <div class="col">
          <label>Custo (salt rounds)</label>
          <select name="cost">
            <?php
              foreach ([8,9,10,11,12,13,14] as $c) {
                $sel = ($c == $cost || ($cost===0 && $c===10)) ? 'selected' : '';
                echo "<option value=\"$c\" $sel>$c</option>";
              }
            ?>
          </select>
        </div>
        <div class="col">
          <label class="small">Dica</label>
          <div class="result small">Custo 10–12 é um bom equilíbrio para a maioria dos servidores.</div>
        </div>
      </div>

      <div class="actions">
        <button type="submit">Gerar hash $2y$</button>
        <button type="reset" class="ghost">Limpar</button>
      </div>
    </form>

    <?php if ($action === 'gen'): ?>
      <div style="margin-top:14px" class="small <?php echo $hashOut ? 'ok' : 'err'; ?>">
        <?php echo e($msgGen); ?>
      </div>
      <label style="margin-top:10px">Hash gerado</label>
      <div class="result" id="hashOut"><?php echo $hashOut ? e($hashOut) : '—'; ?></div>
      <div class="actions">
        <button class="ghost" type="button" onclick="copyHash()">Copiar</button>
        <span class="small">Prefixo deve ser <strong>$2y$</strong>.</span>
      </div>
      <script>
        function copyHash(){
          const el = document.getElementById('hashOut');
          const txt = el ? el.textContent : '';
          if (!txt || txt==='—') return;
          navigator.clipboard?.writeText(txt).then(()=>alert('Hash copiado!')).catch(()=>{
            // fallback
            const ta = document.createElement('textarea');
            ta.value = txt; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); alert('Hash copiado!'); } catch(e){ alert('Copie manualmente.'); }
            document.body.removeChild(ta);
          });
        }
      </script>
      <hr style="border:0;border-top:1px solid #22314f;margin:18px 0">
    <?php endif; ?>

    <!-- Verificar hash -->
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="check">
      <div class="row">
        <div class="col">
          <label>Senha para verificar</label>
          <input name="pwd" type="password" placeholder="Digite a senha" required>
        </div>
        <div class="col">
          <label>Hash (cole aqui)</label>
          <input name="hash_in" type="text" placeholder="$2y$10$..." required>
        </div>
      </div>
      <div class="actions">
        <button type="submit" class="ghost">Verificar</button>
        <?php if ($msgVer): ?>
          <span class="small <?php echo (strpos($msgVer,'✅')!==false)?'ok':'err'; ?>"><?php echo e($msgVer); ?></span>
        <?php endif; ?>
      </div>
    </form>

    <p class="small" style="margin-top:16px">
      Observações: guarde apenas o <em>hash</em>; nunca armazene a senha em texto puro. Para login, use <code>password_verify($senha, $hash)</code>.
    </p>
  </main>
</body>
</html>
