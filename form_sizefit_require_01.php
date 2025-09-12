<html>
<head>
  <meta charset="utf-8">
  <title>画像のサイズ変更</title>
</head>
<body>
<!--   henkan.php              -->
<form action="06_ga_SizeFit_require_01.php" method="get">
<p>▼横か縦どちらかのサイズを選択</p>        
  <p>横サイズ指定：
      <input type="radio" name="horizontal_size" value="700">700pix
    <br>　　　　　　　
      <input type="radio" name="horizontal_size" value="800">800pix（comico推奨）
    <br>　　　　　　　
    <input type="radio" name="horizontal_size" value="1600">1600pix（R-TOON用） 
    <br>　　　　　　　  サイズ入力：      
      <input type="number" name="horizontal_size_input" value=""> pix
     </p>

     <p>縦サイズ指定：
    <input type="radio" name="vertical_size" value="1000">1000pix 
    <br>　　　　　　　
    <!--    <input type="radio" name="vertical_size" value="18000">20,000pix以内(18,000pix前後を設定)   --> 
    <input type="radio" name="vertical_size" value="2000">2000pix
    <br>　　　　　　　
    <input type="radio" name="vertical_size" value="2560">2560pix（R-TOON用）
    <br>　　　　　　　
    <input type="radio" name="vertical_size" value="10000">10000pix（comico推奨）
    <br>　　　　　　　  サイズ入力：
     <input type="number" name="vertical_size_input" value=""> pix
  </p>

    <p>解像度指定　：
    <input type="radio" name="resolution" value="72">72dpi　
    <input type="radio" name="resolution" value="150">150dpi　 
    <input type="radio" name="resolution" value="300">300dpi　
    <input type="radio" name="resolution" value="350">350dpi（comico推奨）  
    </p>

    <p><input type="submit" name="submitBtn" value="送信"></p>
  </form>

<?php
// 横サイズの取得（入力優先、なければラジオボタン）
$resize_w = 0;
if (!empty($_GET['horizontal_size_input'])) {
    $resize_w = (int)$_GET['horizontal_size_input'];
} elseif (!empty($_GET['horizontal_size'])) {
    $resize_w = (int)$_GET['horizontal_size'];
}

// 縦サイズの取得（入力優先、なければラジオボタン）
$resize_h = 0;
if (!empty($_GET['vertical_size_input'])) {
    $resize_h = (int)$_GET['vertical_size_input'];
} elseif (!empty($_GET['vertical_size'])) {
    $resize_h = (int)$_GET['vertical_size'];
}

// 解像度
$resolution = isset($_GET['resolution']) ? (int)$_GET['resolution'] : 72;

// どちらか一方のみ指定されているかチェック
if ($resize_w > 0 && $resize_h > 0) {
    echo "横サイズと縦サイズの両方が指定されています。どちらか一方だけ指定してください。";
    exit;
} elseif ($resize_w > 0) {
    // 横サイズ指定でリサイズ
    // 例: $im->resizeImage($resize_w, 0, Imagick::FILTER_LANCZOS, 1);
    echo "横サイズ指定: {$resize_w}px, 解像度: {$resolution}dpi";
} elseif ($resize_h > 0) {
    // 縦サイズ指定でリサイズ
    // 例: $im->resizeImage(0, $resize_h, Imagick::FILTER_LANCZOS, 1);
    echo "縦サイズ指定: {$resize_h}px, 解像度: {$resolution}dpi";
} else {
    echo "サイズが指定されていません。";
    exit;
}
?>
</body>
</html>


