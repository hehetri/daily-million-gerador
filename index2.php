<?php
// index.php
// 1) busca o HTML da página de histórico
$url = 'https://www.lottery.ie/results/daily-million/history';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

// 2) parseia com DOMDocument
$dom = new DOMDocument;
@$dom->loadHTML($html);

// 3) coleta todas as linhas da tabela de resultados
$rows = $dom->getElementsByTagName('tr');

$freqMain  = [];
$freqBonus = [];

// 4) itera linhas, extrai data e números
foreach ($rows as $tr) {
    $tds = $tr->getElementsByTagName('td');
    if ($tds->length < 8) continue; // pula cabeçalhos ou linhas inválidas

    // exemplo: [0]=data, [1]…[6]=números, [7]=bônus
    $dateStr = trim($tds->item(0)->textContent);
    $date    = DateTime::createFromFormat('d/m/Y', $dateStr);
    // filtrar entre 28/01/2025 e 28/04/2025
    if ($date < new DateTime('2025-01-28') || $date > new DateTime('2025-04-28')) {
        continue;
    }

    // contagem dos 6 principais
    for ($i = 1; $i <= 6; $i++) {
        $n = (int) trim($tds->item($i)->textContent);
        if ($n < 1) continue;
        if (!isset($freqMain[$n])) $freqMain[$n] = 0;
        $freqMain[$n]++;
    }
    // contagem do bônus
    $b = (int) trim($tds->item(7)->textContent);
    if ($b > 0) {
        if (!isset($freqBonus[$b])) $freqBonus[$b] = 0;
        $freqBonus[$b]++;
    }
}

// 5) ordena desc e pega top N
arsort($freqMain);
arsort($freqBonus);

$topMain  = array_slice(array_keys($freqMain), 0, 12);
$topBonus = array_slice(array_keys($freqBonus), 0, 6);

// 6) saída HTML + JavaScript
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gerador Daily Million Dinâmico</title>
  <style>
    :root {
      --primary: #4CAF50; --bg: #f4f4f4; --fg: #333;
      --card: #fff; --r: 12px; --s: 0 4px 8px rgba(0,0,0,0.1);
    }
    * { box-sizing: border-box; }
    body { font-family: sans-serif; background: var(--bg);
      margin:0; display:flex; align-items:center; justify-content:center;
      min-height:100vh; padding:20px;
    }
    .card { background: var(--card); padding:30px;
      border-radius: var(--r); box-shadow: var(--s); text-align:center;
      width:100%; max-width:400px;
    }
    button { background: var(--primary); color:#fff;
      border:none; padding:15px 30px; border-radius:var(--r);
      cursor:pointer; font-size:16px; transition: .3s;
    }
    button:hover { background: #45a049; }
    .seq { margin-top:20px; text-align:left; }
    .seq p { background:#e8f5e9; padding:10px;
      border-radius:var(--r); box-shadow: var(--s);
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>Gerador Daily Million</h2>
    <button id="btn">GERAR SEQUÊNCIAS</button>
    <div id="out" class="seq"></div>
  </div>

  <script>
    // recebe do PHP as listas já calculadas
    const topMain  = <?php echo json_encode($topMain); ?>;
    const topBonus = <?php echo json_encode($topBonus); ?>;

    document.getElementById('btn').addEventListener('click', () => {
      const out = document.getElementById('out');
      out.innerHTML = '';

      for (let i = 1; i <= 3; i++) {
        // escolhe 6 números únicos
        let nums = [];
        while (nums.length < 6) {
          const n = topMain[Math.floor(Math.random() * topMain.length)];
          if (!nums.includes(n)) nums.push(n);
        }
        nums.sort((a,b)=>a-b);
        // escolhe bônus
        const bonus = topBonus[Math.floor(Math.random() * topBonus.length)];

        const p = document.createElement('p');
        p.innerHTML = `<strong>Seq ${i}:</strong> ${nums.join(', ')} — <em>Bônus:</em> ${bonus}`;
        out.appendChild(p);
      }
    });
  </script>
</body>
</html>
