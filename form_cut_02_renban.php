<html>
<head>
  <meta charset="utf-8">
  <title>カットのサイズ指定（連番出力）</title>
</head>
<body>
<!--   henkan.php              -->
<form action="03_ga_cut_107.php" method="get">
        
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
</body>
</html>


