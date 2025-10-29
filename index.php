<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/lib/auth.php';

$rs = mysqli_query($con, "SELECT *, LENGTH(foto_blob) as size_blob FROM disfraces WHERE eliminado=0 ORDER BY votos DESC, id");
$totalVotos = 0;
if ($rs) {
  $rows = [];
  while ($r = mysqli_fetch_assoc($rs)) {
    // Verificar si el usuario ya votó este disfraz
    if (is_logged()) {
      $checkVoto = mysqli_query($con, "SELECT id FROM votos WHERE id_usuario=".user_id()." AND id_disfraz=".(int)$r['id']);
      $r['ya_voto'] = ($checkVoto && mysqli_num_rows($checkVoto) > 0);
    } else {
      $r['ya_voto'] = false;
    }
    $rows[] = $r;
    $totalVotos += (int)$r['votos'];
  }
}
?>
<!doctype html>
<html lang="es"><head>
  <meta charset="utf-8">
  <title>🎃 Halloween Votes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/halloween.css">
</head>
<body>
<header class="header" id="mainHeader">
  <div class="brand">🎃 Halloween Votes</div>
  <nav class="nav small">
    <?php if (is_logged()): ?>
      Hola, <?= h(user_nombre()) ?>!
      <a href="logout.php">Salir</a>
      <?php if (is_admin()): ?>
        <a href="admin.php">Admin</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="registro.php">Registrarme</a>
      <a href="login.php">Entrar</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
  <h1>🎃 Disfraces disponibles 🎃</h1>

  <?php if (!empty($rows) && count($rows) > 0): ?>
    <div class="grid">
      <?php foreach ($rows as $r): ?>
        <div class="card" onclick="openModal(<?= (int)$r['id'] ?>)">
          <div class="card-image-wrapper">
            <?php
              // Determinar qué imagen mostrar
              if (!empty($r['foto']) && file_exists("fotos/".$r['foto'])) {
                echo '<img src="fotos/'.h($r['foto']).'" alt="'.h($r['nombre']).'">';
              } elseif (!empty($r['size_blob'])) {
                $rsBlob = mysqli_query($con, "SELECT foto_blob FROM disfraces WHERE id=".(int)$r['id']);
                if ($rsBlob && ($b = mysqli_fetch_assoc($rsBlob)) && !empty($b['foto_blob'])) {
                  $base64 = 'data:image/jpeg;base64,' . base64_encode($b['foto_blob']);
                  echo '<img src="'.$base64.'" alt="'.h($r['nombre']).'">';
                } else {
                  echo '<img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%231a1a2e\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23666\' font-family=\'Arial\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E🎃 Sin imagen%3C/text%3E%3C/svg%3E" alt="Sin imagen">';
                }
              } else {
                echo '<img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%231a1a2e\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23666\' font-family=\'Arial\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E🎃 Sin imagen%3C/text%3E%3C/svg%3E" alt="Sin imagen">';
              }
            ?>
          </div>

          <div class="card-content">
            <h3><?= h($r['nombre']) ?></h3>
            <p><?= h($r['descripcion']) ?></p>

            <div class="meta">
              <div>
                <strong>Votos: <?= (int)$r['votos'] ?></strong>
                <?php
                  $porc = ($totalVotos > 0) ? ($r['votos'] * 100.0 / $totalVotos) : 0.0;
                  echo ' <span class="badge">'.number_format($porc, 2, ',', '.').'%</span>';
                ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Modal para ver detalles -->
    <div id="modal" class="modal" onclick="closeModalOnOutside(event)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-body" id="modalBody">
          <!-- Contenido dinámico -->
        </div>
      </div>
    </div>

    <script>
    const disfraces = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const totalVotos = <?= $totalVotos ?>;
    const isLogged = <?= is_logged() ? 'true' : 'false' ?>;
    const userId = <?= is_logged() ? user_id() : 'null' ?>;

    function openModal(id) {
      const disfraz = disfraces.find(d => d.id == id);
      if (!disfraz) return;

      const porc = totalVotos > 0 ? ((disfraz.votos / totalVotos) * 100).toFixed(2) : '0.00';
      
      // Determinar la imagen a mostrar
      let imgSrc = '';
      if (disfraz.foto && disfraz.foto !== '') {
        imgSrc = 'fotos/' + disfraz.foto;
      } else if (disfraz.size_blob > 0) {
        // Para blob, necesitaríamos hacer una petición, por ahora usamos placeholder
        imgSrc = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%231a1a2e\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23666\' font-family=\'Arial\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E🎃 Imagen del disfraz%3C/text%3E%3C/svg%3E';
      } else {
        imgSrc = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%231a1a2e\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23666\' font-family=\'Arial\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E🎃 Sin imagen%3C/text%3E%3C/svg%3E';
      }

      const yaVoto = disfraz.ya_voto || false;

      const modalBody = document.getElementById('modalBody');
      modalBody.innerHTML = `
        <div class="modal-image">
          <img src="${imgSrc}" alt="${escapeHtml(disfraz.nombre)}">
        </div>
        <div class="modal-info">
          <div>
            <h2>🎃 ${escapeHtml(disfraz.nombre)}</h2>
            <p class="description">${escapeHtml(disfraz.descripcion)}</p>
          </div>
          
          <div class="modal-stats">
            <div class="stat-row">
              <span class="stat-label">Total de votos</span>
              <span class="stat-value">${disfraz.votos}</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Porcentaje</span>
              <span class="stat-value">${porc.replace('.', ',')}%</span>
            </div>
          </div>

          <div class="modal-actions">
            ${isLogged ? `
              <form method="post" action="votar.php" style="flex: 1">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                <input type="hidden" name="id_disfraz" value="${disfraz.id}">
                <button class="btn" type="submit" ${yaVoto ? 'disabled' : ''} style="width:100%">
                  ${yaVoto ? '✓ Ya votaste' : '🎃 Votar'}
                </button>
              </form>
            ` : `
              <a href="login.php" class="btn secondary" style="flex: 1; text-align: center; text-decoration: none;">
                Inicia sesión para votar
              </a>
            `}
            <button class="btn secondary" onclick="closeModal()">← Volver</button>
          </div>
        </div>
      `;

      // Ocultar el header - usar querySelector directo
      const header = document.querySelector('.header');
      if (header) {
        header.classList.add('hidden');
      }
      
      document.getElementById('modal').classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      // Mostrar el header - usar querySelector directo
      const header = document.querySelector('.header');
      if (header) {
        header.classList.remove('hidden');
      }
      
      document.getElementById('modal').classList.remove('active');
      document.body.style.overflow = '';
    }

    function closeModalOnOutside(event) {
      if (event.target.id === 'modal') {
        closeModal();
      }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Cerrar con tecla ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
    </script>
  <?php else: ?>
    <p>No hay disfraces cargados aún.</p>
  <?php endif; ?>
</main>
</body>
</html>