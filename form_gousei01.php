<html>
<head>
  <meta charset="utf-8">
  <title>連結のサイズ指定</title>
</head>
<body>
<!--   henkan.php              -->
<form action="02_ga_gousei_10.php" method="get">

    <p>タイトル略称：<input type="text" name="short_name">
    </p>
        
    <p>横サイズ指定：
      <input type="radio" name="horizontal_size" value="700"  checked>700pix
      <input type="radio" name="horizontal_size" value="800">800pix 
    </p>

    <p>縦サイズ指定：
    <input type="radio" name="vertical_size" value="25000"  checked>30,000pix以内(25,000pix以内を設定) 
<!--    <input type="radio" name="vertical_size" value="18000">20,000pix以内(18,000pix前後を設定)   --> 
    <input type="radio" name="vertical_size" value="15000">20,000pix以内ver.2(15,000pix前後を設定)  
    </p>

    <p>解像度指定　：
    <input type="radio" name="resolution" value="72"  checked>72dpi
    <input type="radio" name="resolution" value="300">300dpi  
    </p>

    <p><input type="submit" name="submitBtn" value="送信"></p>
  </form>
</body>
</html>


