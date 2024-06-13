<?php
	  $file_name = $_FILES['image']['name'];
      $file_tmp =$_FILES['image']['tmp_name'];

move_uploaded_file($file_tmp,"./".$file_name);

?>

<!DOCTYPE html>
<html>
<body>

<form method="post" enctype="multipart/form-data">
  Select image to upload:
  <input type="file" name="image" id="fileToUpload">
  <input type="submit" value="Upload Image" name="submit">
</form>

</body>
</html>