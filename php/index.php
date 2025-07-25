<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$APPLICATION_KEY = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
$FRONT_END_SOURCE = "https://cdn-example.com/uploads/".date("Ymd")."/";

$BACK_END_SOURCE = __DIR__."/uploads/".date("Ymd")."/";







function decrypt($encryptedData, $password) {
  $method = 'aes-256-cbc';
  $password = substr(hash('sha256', $password, true), 0, 32);
  $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
  $decrypted = openssl_decrypt(base64_decode($encryptedData), $method, $password, OPENSSL_RAW_DATA, $iv);
  $checksum = substr($decrypted,0,4);
  $message  = substr($decrypted,4);
  if(substr(md5($message),0,4)==$checksum){
    return $message;
  }else{
    return "";
  }  
}



function getBearerToken(){
  $headers = array_change_key_case(getallheaders(), CASE_LOWER);
  if (!isset($headers['authorization'])) {
    return false;
  }
  return trim(str_replace('Bearer', '', $headers['authorization']));
}


function uploadImage($name = "image", $height = 512) {
  global $BACK_END_SOURCE, $FRONT_END_SOURCE;

  if (!is_dir($BACK_END_SOURCE)) {
    mkdir($BACK_END_SOURCE, 0755, true);
  }

  $response = null;

  if (isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
    // Get the file details
    $fileTmpPath = $_FILES[$name]['tmp_name'];
    $fileName = $_FILES[$name]['name'];
    $fileSize = $_FILES[$name]['size'];
    $fileType = $_FILES[$name]['type'];

    // Ensure the file size is less than 2MB (for JPG, JPEG, PNG, WebP, GIF)
    if ($fileSize > 2 * 1024 * 1024) {
      die("File size must be less than 2MB.");
    }

    // Get the file extension
    $imageExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $type = exif_imagetype($fileTmpPath);
    if($type===1) $imageExtension = "gif";
    if($type===2) $imageExtension = "jpeg";
    if($type===3) $imageExtension = "png";
    if($type===18) $imageExtension = "webp";
    
    

    // Handle SVG and GIF files separately (size check for SVG)
    if ($imageExtension == 'mp4' || $imageExtension == 'mkv' || $imageExtension=="webm") {
      if ($fileSize > 30 * 1024 * 1024) {
        die("SVG file size must be less than 1MB.");
      }

      // Set the target folder for saving the file
      $targetFolder = $BACK_END_SOURCE;
      $newFileName = uniqid() . '.' . $imageExtension;

      // Move the SVG or GIF file directly to the target folder
      if (move_uploaded_file($fileTmpPath, $targetFolder . $newFileName)) {
        $response = $FRONT_END_SOURCE . $newFileName;
      } else {
        die("Failed to move file.");
      }
    }else if ($imageExtension === 'svg' || $imageExtension === 'gif') {
      if ($fileSize > 1 * 1024 * 1024) {
        die("SVG file size must be less than 1MB.");
      }

      // Set the target folder for saving the file
      $targetFolder = $BACK_END_SOURCE;
      $newFileName = uniqid() . '.' . $imageExtension;

      // Move the SVG or GIF file directly to the target folder
      if (move_uploaded_file($fileTmpPath, $targetFolder . $newFileName)) {
        $response = $FRONT_END_SOURCE . $newFileName;
      } else {
        die("Failed to move file.");
      }
    }else if ($imageExtension === 'jpg' || $imageExtension === 'jpeg' || $imageExtension=="png" || $imageExtension=="webp") {
      // Only allow JPG, JPEG, PNG, and WebP
      if (!in_array($imageExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
        die("Invalid file type. Only JPG, JPEG, PNG, and WebP are allowed.");
      }

      // Set the target folder
      $targetFolder = $BACK_END_SOURCE;
      $newFileName = uniqid() . '.webp';

      // Create image resource based on type
      switch ($imageExtension) {
        case 'jpg':
        case 'jpeg':
          $image = imagecreatefromjpeg($fileTmpPath);
          break;
        case 'png':
          $image = imagecreatefrompng($fileTmpPath);
          break;
        case 'webp':
          $image = imagecreatefromwebp($fileTmpPath);
          break;
        default:
          die("Failed to load image.");
      }

      if (!$image) {
        die("Failed to create image from uploaded file.");
      }

      // Get original image dimensions
      list($originalWidth, $originalHeight) = getimagesize($fileTmpPath);
      $width = (int) ($originalWidth * ($height / $originalHeight));

      // Create a true-color image for resizing
      $resizedImage = imagecreatetruecolor($width, $height);

      // Preserve transparency for PNG
      if ($imageExtension === 'png') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefill($resizedImage, 0, 0, $transparent);
      }

      // Resize and copy the image
      imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

      // Save as WebP with quality 80
      $webpFilePath = $targetFolder . $newFileName;
      $saved = imagewebp($resizedImage, $webpFilePath, 80);


      // Free memory
      imagedestroy($image);
      imagedestroy($resizedImage);

      // Check if the image was saved successfully
      if ($saved) {
        $response = $FRONT_END_SOURCE . $newFileName;
      } else {
        die("Failed to save the image.");
      }
    }else{
      if ($fileSize > 20 * 1024 * 1024) {
        die("file size must be less than 1MB.");
      }

      // Set the target folder for saving the file
      $targetFolder = $BACK_END_SOURCE;
      $newFileName = uniqid() . '.' . $imageExtension;

      // Move the SVG or GIF file directly to the target folder
      if (move_uploaded_file($fileTmpPath, $targetFolder . $newFileName)) {
        $response = $FRONT_END_SOURCE . $newFileName;
      } else {
        die("Failed to move file.");
      }
    }
  }
  return $response;
}


$user = getBearerToken() ? json_decode(decrypt(getBearerToken(),$APPLICATION_KEY),true) : false;


if(isset($_FILES["file"]) && $user && $user['user_id']>0 && $user['group_id']==1){  
  $FRONT_END_SOURCE .= $user['user_id'] . "/";
  $BACK_END_SOURCE  .= $user['user_id'] . "/";
  $file = uploadImage("file",720);
  echo $file;
  exit();
}

?>

<style>
  body,html,*{
    padding:0;
    margin:0;
    box-sizing:border-box;
  }
  body{
    cursor:pointer;
  }
</style>


<script>

  let AUTH_KEY = "";
  
  // LISTEN KEY
  window.addEventListener("DOMContentLoaded",function(){
    debugger
    window.parent.postMessage(JSON.stringify({action:"<auth",url:location.href}), '*');
  })

  // GET KEY
  window.addEventListener('message', (event) => {
    if (!event.origin.endsWith("topluyo.com")) return;
    let data = JSON.parse(event.data)
    debugger
    if(data[">auth"]){
      AUTH_KEY = data[">auth"];
      console.log(AUTH_KEY);
    }
  })

  // LEARN USER
  window.onclick = function(){

    let input = document.createElement("input");
    input.type = "file";
    //input.accept = "image/*"; 
    input.oninput = function(event){
      const form = new FormData();
      form.append("file",input.files[0]);

      /*
      fetch('', {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + AUTH_KEY
        },
        body: form
      })
        .then(res => res.text())
        .then(data => console.log(data))
        .catch(err => console.error(err));  

      */

      const xhr = new XMLHttpRequest();

      xhr.open('POST', '');

      xhr.setRequestHeader('Authorization', 'Bearer ' + AUTH_KEY);
      
      document.getElementById("image").style.display="none"
      document.getElementById("loading").style.display=null
      function loading(ratio){
        document.getElementById("loading").style.setProperty("--ratio",ratio)
      }

      // Upload progress
      xhr.upload.onprogress = function (event) {
        if (event.lengthComputable) {
          const percent = (event.loaded / event.total);
          console.log(`Uploaing: ${percent}`);
          loading(percent)
        }
      };

      // On complete
      xhr.onload = function () {
        if (xhr.status === 200) {
          console.log('Success:', xhr.responseText);
          window.parent.postMessage({action:"<share",url:location.href,data:xhr.responseText}, '*');
          setTimeout(()=>{
            document.getElementById("loading").style.display="none"
            document.getElementById("image").style.display=null
            document.getElementById("loading").style.setProperty("--ratio",0)
          },200)
          loading(1)
        } else {
          console.error('Error:', xhr.status, xhr.statusText);
        }
      };

      // On error
      xhr.onerror = function () {
        console.error('Upload failed.');
      };

      xhr.send(form);




    };
    input.click()

    
  }
</script>


<svg id="image" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#888" style="width: 100%;height: 100%;padding: .2em;"><path d="M480-480ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h320v80H200v560h560v-280h80v280q0 33-23.5 56.5T760-120H200Zm40-160h480L570-480 450-320l-90-120-120 160Zm480-280v-167l-64 63-56-56 160-160 160 160-56 56-64-63v167h-80Z"></path></svg>


<style>
.loading-circle {
  width: 100px;
  height: 100px;
  transform-origin:center;
  transform: rotate(-90deg); /* start from top */

  stroke: #4caf50;
  fill: none;
  stroke-width: 10;
  stroke-linecap: round;
  stroke-dasharray: 252; /* 2πr where r = 50 (half of viewBox size) */
  stroke-dashoffset: calc(252 - (var(--ratio) * 252));
  transition: stroke-dashoffset 0.3s ease;
}
</style>

<svg id="loading" viewBox="0 0 100 100" style="width:100%;height:100%;display:none;--ratio:0">
  <circle class="loading-circle" cx="50" cy="50" r="40" />
</svg>
