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
    cursor:not-allowed;
    opacity: 0.5;
    transition: all 0.3s ease;
  }
</style>

<script src="
https://cdn.jsdelivr.net/npm/@nsfw-filter/nsfwjs@2.2.0/dist/nsfwjs.min.js
"></script>
<script src="
https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0/dist/tf.min.js
"></script>

<script>

  let AUTH_KEY = "";
  
  let nfswTolerance = 0.3; // 0.3 is the default tolerance for NSFWJS
  const modelUrl = 'https://raw.githubusercontent.com/nsfw-filter/nsfwjs/master/example/nsfw_demo/public/model/';
  let nfswModel = null;
  let modelLoaded = false;
  const nfswIsEnabled = true; // NSFW model is enabled by default

  // LISTEN KEY
  window.addEventListener("DOMContentLoaded",async function(){
    window.parent.postMessage(JSON.stringify({action:"<auth",url:location.href}), '*');
    
    if (nfswIsEnabled) {
      try {
        nfswModel = await nsfwjs.load(modelUrl, {
          size: 299,
          numThreads: 2,
          onProgress: (progress) => {
            console.log(`Model loading progress: ${progress * 100}%`);
          }
        });
        modelLoaded = true;
        console.log("NSFW Model loaded");
      } catch (error) {
        console.error("Failed to load NSFW model:", error);
        modelLoaded = false;
      }
    } else {
      console.log("NSFW model disabled");
      modelLoaded = false;
    }
    
    // Enable file upload after model is loaded or if NSFW is disabled
    document.body.style.cursor = "pointer";
    document.body.style.opacity = "1";
  })

  // GET KEY
  window.addEventListener('message', (event) => {
    if (!event.origin.endsWith("topluyo.com")) return;
    let data = JSON.parse(event.data)
    if(data[">auth"]){
      AUTH_KEY = data[">auth"];
      console.log(AUTH_KEY);
    }
  })

  // LEARN USER
  window.onclick = function(){
    // NSFW aktifse ve model yüklenmeden dosya yüklemeye izin verme
    if (nfswIsEnabled && !modelLoaded) {
      alert("Model henüz yüklenmedi, lütfen bekleyin...");
      return;
    }

    let input = document.createElement("input");
    input.type = "file";
    input.accept = "*/*"; // Tüm dosya türlerini kabul et
    console.log("NSFW model loaded:", nfswModel);
    
    input.oninput = async function(event){
      const file = input.files[0];
      if (!file) return;
      
      const fileType = file.type;
      const isImage = fileType.startsWith('image/');
      const isVideo = fileType.startsWith('video/');
      const isGif = fileType === 'image/gif';
      
      // NSFW enabled ve image/video/gif dosyaları için NSFW kontrolü yap
      if (nfswIsEnabled && (isImage || isVideo || isGif) && nfswModel) {
        try {
          let shouldBlock = false;
          
          if (isImage && !isGif) {
            // Normal image dosyaları için NSFW kontrolü
            const img = new Image();
            img.onload = async function() {
              const predictions = await nfswModel.classify(img);
              console.log("NSFW Predictions:", predictions);
              
              // NSFW içerik kontrolü (Porn, Sexy ve Hentai)
              const pornScore = predictions.find(p => p.className === 'Porn')?.probability || 0;
              const sexyScore = predictions.find(p => p.className === 'Sexy')?.probability || 0;
              const hentaiScore = predictions.find(p => p.className === 'Hentai')?.probability || 0;
              const maxNsfwScore = Math.max(pornScore, sexyScore, hentaiScore);
              
              if (maxNsfwScore > nfswTolerance) {
                const detectedType = pornScore > sexyScore && pornScore > hentaiScore ? 'Porn' : 
                                   sexyScore > hentaiScore ? 'Sexy' : 'Hentai';
                alert(`Bu dosya uygunsuz içerik (${detectedType}) barındırdığı için yüklenemez.`);
                return;
              }
              
              // Güvenliyse dosyayı yükle
              uploadFile(file);
            };
            img.src = URL.createObjectURL(file);
          } else if (isGif) {
            // GIF dosyaları için frame-by-frame analiz
            console.log("GIF dosyası analiz ediliyor...");
            const img = new Image();
            img.onload = async function() {
              try {
                const myConfig = {
                  topk: 1,
                  fps: 1,
                  onFrame: ({ index, totalFrames, predictions, image }) => {
                    console.log(`GIF Frame ${index}/${totalFrames}:`, predictions);
                    
                    // Her frame için NSFW kontrolü (Porn, Sexy ve Hentai)
                    const pornScore = predictions.find(p => p.className === 'Porn')?.probability || 0;
                    const sexyScore = predictions.find(p => p.className === 'Sexy')?.probability || 0;
                    const hentaiScore = predictions.find(p => p.className === 'Hentai')?.probability || 0;
                    const maxNsfwScore = Math.max(pornScore, sexyScore, hentaiScore);
                    
                    if (maxNsfwScore > nfswTolerance) {
                      const detectedType = pornScore > sexyScore && pornScore > hentaiScore ? 'Porn' : 
                                         sexyScore > hentaiScore ? 'Sexy' : 'Hentai';
                      alert(`Bu GIF dosyası ${index}. frame'de uygunsuz içerik (${detectedType}) barındırdığı için yüklenemez.`);
                      return false; // Analizi durdur
                    }
                    return true;
                  }
                };
                
                const framePredictions = await nfswModel.classifyGif(img, myConfig);
                
                // Tüm frame'ler güvenli ise dosyayı yükle
                let hasUnsafeContent = false;
                for (let prediction of framePredictions) {
                  const pornScore = prediction.find(p => p.className === 'Porn')?.probability || 0;
                  const sexyScore = prediction.find(p => p.className === 'Sexy')?.probability || 0;
                  const hentaiScore = prediction.find(p => p.className === 'Hentai')?.probability || 0;
                  const maxNsfwScore = Math.max(pornScore, sexyScore, hentaiScore);
                  
                  if (maxNsfwScore > nfswTolerance) {
                    hasUnsafeContent = true;
                    break;
                  }
                }
                
                if (hasUnsafeContent) {
                  alert("Bu GIF dosyası uygunsuz içerik barındırdığı için yüklenemez.");
                } else {
                  console.log("GIF dosyası güvenli, yükleniyor...");
                  uploadFile(file);
                }
              } catch (error) {
                console.error("GIF analizi sırasında hata:", error);
                // Hata durumunda dosyayı yükle
                uploadFile(file);
              }
            };
            img.src = URL.createObjectURL(file);
          } else if (isVideo) {
            // Video dosyaları için belirli karelerde analiz
            console.log("Video dosyası analiz ediliyor...");
            const video = document.createElement('video');
            video.muted = true;
            video.preload = 'metadata';
            
            video.onloadedmetadata = async function() {
              const canvas = document.createElement('canvas');
              const ctx = canvas.getContext('2d');
              canvas.width = video.videoWidth;
              canvas.height = video.videoHeight;
              
              const duration = video.duration;
              let framesToCheck = [];
              
              // Video uzunluğuna göre kontrol edilecek frame sayısını belirle
              if (duration <= 10) {
                // 10 saniyeden kısa videolar için her saniye
                framesToCheck = Array.from({length: Math.ceil(duration)}, (_, i) => i);
              } else if (duration <= 60) {
                // 1 dakikadan kısa videolar için her 5 saniye
                framesToCheck = Array.from({length: Math.ceil(duration/5)}, (_, i) => i * 5);
              } else {
                // Uzun videolar için her 15 saniye
                framesToCheck = Array.from({length: Math.ceil(duration/15)}, (_, i) => i * 15);
              }
              
              // İlk ve son frame'i de ekle
              if (!framesToCheck.includes(0)) framesToCheck.unshift(0);
              if (!framesToCheck.includes(Math.floor(duration-1))) framesToCheck.push(Math.floor(duration-1));
              
              console.log(`Video süresi: ${duration}s, Kontrol edilecek frame'ler:`, framesToCheck);
              
              let hasUnsafeContent = false;
              
              for (let timeToCheck of framesToCheck) {
                if (hasUnsafeContent) break;
                
                video.currentTime = timeToCheck;
                
                await new Promise((resolve) => {
                  video.onseeked = resolve;
                });
                
                // Frame'i canvas'a çiz
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                try {
                  const predictions = await nfswModel.classify(canvas);
                  console.log(`Video frame ${timeToCheck}s:`, predictions);
                  
                  const pornScore = predictions.find(p => p.className === 'Porn')?.probability || 0;
                  const sexyScore = predictions.find(p => p.className === 'Sexy')?.probability || 0;
                  const hentaiScore = predictions.find(p => p.className === 'Hentai')?.probability || 0;
                  const maxNsfwScore = Math.max(pornScore, sexyScore, hentaiScore);
                  
                  if (maxNsfwScore > nfswTolerance) {
                    const detectedType = pornScore > sexyScore && pornScore > hentaiScore ? 'Porn' : 
                                       sexyScore > hentaiScore ? 'Sexy' : 'Hentai';
                    alert(`Bu video dosyası ${timeToCheck}. saniyede uygunsuz içerik (${detectedType}) barındırdığı için yüklenemez.`);
                    hasUnsafeContent = true;
                    break;
                  }
                } catch (error) {
                  console.error(`Frame ${timeToCheck}s analizi sırasında hata:`, error);
                }
              }
              
              if (!hasUnsafeContent) {
                console.log("Video dosyası güvenli, yükleniyor...");
                uploadFile(file);
              }
            };
            
            video.src = URL.createObjectURL(file);
          } else {
            // Diğer image türleri için direkt yükle
            uploadFile(file);
          }
        } catch (error) {
          console.error("NSFW analizi sırasında hata:", error);
          // Hata durumunda dosyayı yükle
          uploadFile(file);
        }
      } else {
        // NSFW disabled veya image/video olmayan dosyalar için direkt yükle
        console.log(nfswIsEnabled ? "Non-media file, uploading directly" : "NSFW disabled, uploading directly");
        uploadFile(file);
      }
    };
    
    function uploadFile(file) {
      const form = new FormData();
      form.append("file", file);

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
    }

    input.click();
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
