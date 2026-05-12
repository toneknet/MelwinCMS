<?php
/**
Name: Media Library
Description: Enkel mediahantering (upload + metadata + val i formulär)
Version: 0.1
Type: Library
*/

class media_library
{
    private $defaultUploadTypes = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'mp4', 'avi', 'mpeg', 'mp3', 'ogg', 'wav');
    private $uploadDirRelative = "";
    private $uploadDirAbsolute = "";
    private $metaFile = "";

    function __construct()
    {
        if (!self::isEnabled()) {
            throw new Exception("Media library är inaktiverat. Sätt CONFIG uploadDir.");
        }

        $relative = trim($GLOBALS['CONFIG']['uploadDir']);
        $relative = trim($relative, "/\\");

        if ($relative === "") {
            throw new Exception("uploadDir saknas.");
        }

        $this->uploadDirRelative = $relative . "/";
        $this->uploadDirAbsolute = ROOTPATH . $this->uploadDirRelative;

        if (!is_dir($this->uploadDirAbsolute)) {
            if (!mkdir($this->uploadDirAbsolute, 0775, true) && !is_dir($this->uploadDirAbsolute)) {
                throw new Exception("Kunde inte skapa upload-katalogen.");
            }
        }

        $this->metaFile = $this->uploadDirAbsolute . ".media-library.json";
    }

    public static function isEnabled()
    {
        return isset($GLOBALS['CONFIG']['uploadDir']) && trim($GLOBALS['CONFIG']['uploadDir']) !== "";
    }

    public function getItems()
    {
        $allowedExtensions = $this->getAllowedExtensions();
        $items = array_values(array_filter($this->readMeta(), function ($item) use ($allowedExtensions) {
            $path = "";
            if (isset($item['relative_path'])) {
                $path = $item['relative_path'];
            } elseif (isset($item['filename'])) {
                $path = $item['filename'];
            }
            $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
            if ($this->isThumbnailFilename(basename($path))) {
                return false;
            }
            return $ext !== "" && in_array($ext, $allowedExtensions, true);
        }));
        usort($items, function ($a, $b) {
            return strcmp($b['uploaded_at'], $a['uploaded_at']);
        });
        foreach ($items as $k => $item) {
            $items[$k]['thumbnail_url'] = $this->resolveThumbnailUrl($item);
        }
        return $items;
    }

    public function getItemById($id)
    {
        $items = $this->readMeta();
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function uploadFromFilesKey($filesKey, $title = "", $alt = "", $tags = "")
    {
        if (!isset($_FILES[$filesKey])) {
            return null;
        }

        $file = $_FILES[$filesKey];
        if (!isset($file['error']) || (int)$file['error'] === 4) {
            return null;
        }

        if ((int)$file['error'] !== 0) {
            throw new Exception("Upload misslyckades. Felkod: " . (int)$file['error']);
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Ogiltig uppladdad fil.");
        }

        $originalName = basename($file['name']);
        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        if (!$this->isAllowedExtension($extension)) {
            throw new Exception("Filtypen ." . $extension . " är inte tillåten för upload.");
        }
        $safeName = $this->sanitizeFilename($originalName);
        $safeName = $this->ensureUniqueFilename($safeName);

        $destination = $this->uploadDirAbsolute . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Kunde inte flytta uppladdad fil.");
        }

        $mimeType = "application/octet-stream";
        if (function_exists('mime_content_type')) {
            $tmp = @mime_content_type($destination);
            if ($tmp !== false) {
                $mimeType = $tmp;
            }
        }

        $id = hash('sha256', $safeName . microtime(true) . random_int(1000, 9999));
        $relativePath = $this->uploadDirRelative . $safeName;
        $item = array(
            'id' => $id,
            'filename' => $safeName,
            'original_name' => $originalName,
            'relative_path' => $relativePath,
            'url' => rtrim(URLBASE, '/') . '/' . $relativePath,
            'mime' => $mimeType,
            'size' => filesize($destination),
            'title' => trim((string)$title),
            'alt' => trim((string)$alt),
            'tags' => $this->normalizeTags($tags),
            'uploaded_at' => date('Y-m-d H:i:s')
        );
        $item['thumbnail_url'] = $this->resolveThumbnailUrl($item);

        $items = $this->readMeta();
        $items[] = $item;
        $this->writeMeta($items);

        return $item;
    }

    public function updateMeta($id, $title = null, $alt = null, $tags = null)
    {
        $items = $this->readMeta();

        foreach ($items as $idx => $item) {
            if ($item['id'] !== $id) {
                continue;
            }

            if (!is_null($title)) {
                $items[$idx]['title'] = trim((string)$title);
            }
            if (!is_null($alt)) {
                $items[$idx]['alt'] = trim((string)$alt);
            }
            if (!is_null($tags)) {
                $items[$idx]['tags'] = $this->normalizeTags($tags);
            }

            $this->writeMeta($items);
            return $items[$idx];
        }

        return null;
    }

    /**
     * Indexerar befintliga filer i upload-katalogen till metadata-json.
     * Filer som redan finns i json (matchat på relative_path) hoppas över.
     *
     * @param bool $recursive Om underkataloger skall indexeras
     * @return array [indexed => int, skipped => int, total => int]
     */
    public function indexExistingFiles($recursive = true)
    {
        $items = $this->readMeta();
        $existingByRelativePath = array();
        foreach ($items as $item) {
            if (isset($item['relative_path'])) {
                $existingByRelativePath[$item['relative_path']] = true;
            }
        }

        $indexed = 0;
        $skipped = 0;
        $allFiles = $this->collectFiles($recursive);

        foreach ($allFiles as $absolutePath) {
            $relativeFilePath = ltrim(str_replace($this->uploadDirAbsolute, '', $absolutePath), '/\\');
            $relativePath = $this->uploadDirRelative . str_replace('\\', '/', $relativeFilePath);
            $extension = strtolower((string)pathinfo($relativePath, PATHINFO_EXTENSION));

            if (isset($existingByRelativePath[$relativePath])) {
                $skipped++;
                continue;
            }
            if (!$this->isAllowedExtension($extension)) {
                $skipped++;
                continue;
            }
            if ($this->isThumbnailFilename(basename($relativePath))) {
                $skipped++;
                continue;
            }

            $filename = basename($absolutePath);
            $mimeType = "application/octet-stream";
            if (function_exists('mime_content_type')) {
                $tmp = @mime_content_type($absolutePath);
                if ($tmp !== false) {
                    $mimeType = $tmp;
                }
            }

            $items[] = array(
                'id' => hash('sha256', $relativePath . filesize($absolutePath) . filemtime($absolutePath)),
                'filename' => $filename,
                'original_name' => $filename,
                'relative_path' => $relativePath,
                'url' => rtrim(URLBASE, '/') . '/' . $relativePath,
                'mime' => $mimeType,
                'size' => filesize($absolutePath),
                'title' => '',
                'alt' => '',
                'tags' => array(),
                'uploaded_at' => date('Y-m-d H:i:s', filemtime($absolutePath))
            );

            $existingByRelativePath[$relativePath] = true;
            $indexed++;
        }

        $this->writeMeta($items);
        return array(
            'indexed' => $indexed,
            'skipped' => $skipped,
            'total' => count($allFiles)
        );
    }

    public function renderPickerField($dbFieldName, $selectedId = "", $caption = null)
    {
        $items = $this->getItems();
        $mediaJsMap = array();

        $out = "";
        if (!is_null($caption)) {
            $out .= "<label for=\"db_{$dbFieldName}\">{$caption}</label>\n";
        }

        $out .= "<select name=\"db_{$dbFieldName}\" id=\"db_{$dbFieldName}\" style=\"display:none;\">\n";
        $out .= "<option value=\"\">- Välj media -</option>\n";
        foreach ($items as $item) {
            $label = $item['title'] !== "" ? $item['title'] : $item['filename'];
            $selected = ($selectedId !== "" && $selectedId === $item['id']) ? " selected" : "";
            $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $safeId = htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8');
            $out .= "<option value=\"{$safeId}\"{$selected}>{$safeLabel}</option>\n";

            $mediaJsMap[$item['id']] = array(
                'title' => $label,
                'url' => isset($item['url']) ? $item['url'] : '',
                'isImage' => (isset($item['mime']) && strpos($item['mime'], 'image/') === 0)
            );
        }
        $out .= "</select>\n";

        $out .= "<div class=\"media-library-current\" id=\"media_current_{$dbFieldName}\">\n";
        $out .= "<div class=\"media-library-current-preview\" id=\"media_current_preview_{$dbFieldName}\">Ingen bild vald</div>\n";
        $out .= "<button type=\"button\" class=\"media-library-open\" id=\"media_open_{$dbFieldName}\" title=\"Öppna bildarkiv\">" . getIconFullSVG('media',strAlt:'Bildarkiv') . "</button>\n";
        $out .= "</div>\n";

        $out .= "<div class=\"media-library-panel\" id=\"media_panel_{$dbFieldName}\" style=\"display:none;\">\n";
        $out .= "<div class=\"media-library-grid\" id=\"media_grid_{$dbFieldName}\">\n";
        $out .= "<button type=\"button\" class=\"media-library-item media-library-item-upload\" data-media-action=\"upload\" title=\"Ladda upp ny bild\">" . getIconFullSVG('upload',strAlt:'Ladda upp') . "</button>\n";
        $out .= "<button type=\"button\" class=\"media-library-item media-library-item-none\" data-media-id=\"\" title=\"Ingen bild\">Ingen bild</button>\n";
        foreach ($items as $item) {
            $safeId = htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8');
            $safeTitle = htmlspecialchars($item['title'] !== "" ? $item['title'] : $item['filename'], ENT_QUOTES, 'UTF-8');
            $isImage = (strpos($item['mime'], 'image/') === 0);
            $out .= "<button type=\"button\" class=\"media-library-item\" data-media-id=\"{$safeId}\" title=\"{$safeTitle}\">";
            if ($isImage) {
                $thumbUrl = isset($item['thumbnail_url']) && $item['thumbnail_url'] !== "" ? $item['thumbnail_url'] : $item['url'];
                $safeUrl = htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8');
                $out .= "<img src=\"{$safeUrl}\" alt=\"{$safeTitle}\" loading=\"lazy\">";
            } else {
                $out .= "<span>{$safeTitle}</span>";
            }
            $out .= "</button>\n";
        }
        $out .= "</div>\n";
        $out .= "</div>\n";

        $out .= "<div class=\"media-library-upload\" id=\"media_upload_box_{$dbFieldName}\" style=\"display:none;\">\n";
        $out .= "<label for=\"media_upload_{$dbFieldName}\">Ladda upp ny fil</label>\n";
        $out .= "<input type=\"file\" name=\"media_upload_{$dbFieldName}\" id=\"media_upload_{$dbFieldName}\">\n";
        $out .= "<input type=\"text\" name=\"media_title_{$dbFieldName}\" placeholder=\"Titel\">\n";
        $out .= "<input type=\"text\" name=\"media_alt_{$dbFieldName}\" placeholder=\"Alt-text\">\n";
        $out .= "<input type=\"text\" name=\"media_tags_{$dbFieldName}\" placeholder=\"Taggar, kommaseparerade\">\n";
        $out .= "</div>\n";

        $grid_size = 150;
        $safeField = htmlspecialchars($dbFieldName, ENT_QUOTES, 'UTF-8');
        $out .= "<style>
        #media_current_{$safeField}{display:flex;align-items:center;gap:8px}
        #media_current_preview_{$safeField}{width:{$grid_size}px;height:{$grid_size}px;border:1px solid #ddd;display:flex;align-items:center;justify-content:center;font-size:12px;overflow:hidden}
        #media_current_preview_{$safeField} img{max-width:100%;max-height:100%;object-fit:contain;display:block}
        #media_open_{$safeField}{border:1px solid #ddd;background:#fff;padding:6px 10px;cursor:pointer}
        #media_open_{$safeField} svg{width:20px;height:20px;display:block}
        #media_panel_{$safeField}{margin-top:8px}
        #media_grid_{$safeField}{display:grid;grid-template-columns:repeat(auto-fill,minmax({$grid_size}px,1fr));gap:8px;margin-top:8px}
        #media_grid_{$safeField} .media-library-item{border:1px solid #ddd;background:#fff;min-height:70px;padding:4px;cursor:pointer;display:flex;align-items:center;justify-content:center; height:unset;}
        #media_grid_{$safeField} .media-library-item.active{border-color:#2d7ff9;box-shadow:0 0 0 1px #2d7ff9 inset}
        #media_grid_{$safeField} .media-library-item img{width:100%;object-fit:contain;display:block}
        #media_grid_{$safeField} .media-library-item img:hover{transform: scale(2);}
        #media_grid_{$safeField} .media-library-item span{font-size:12px;display:block;word-break:break-word}
        #media_grid_{$safeField} .media-library-item-upload{display:flex;align-items:center;justify-content:center}
        #media_grid_{$safeField} .media-library-item-upload svg{width:34px;height:34px}
        #media_upload_box_{$safeField}{margin-top:10px;padding:8px;border:1px solid #eee;background:#fafafa}
        #media_upload_box_{$safeField} input{display:block;width:100%;margin-bottom:6px}
        </style>\n"; // ite img {height:80px;}
        $jsonMap = json_encode($mediaJsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonMap === false) {
            $jsonMap = "{}";
        }
        $out .= "<script>
        (function() {
            var select = document.getElementById('db_{$safeField}');
            var grid = document.getElementById('media_grid_{$safeField}');
            var panel = document.getElementById('media_panel_{$safeField}');
            var uploadBox = document.getElementById('media_upload_box_{$safeField}');
            var openBtn = document.getElementById('media_open_{$safeField}');
            var preview = document.getElementById('media_current_preview_{$safeField}');
            var mediaMap = {$jsonMap};
            if (!select || !grid || !panel || !uploadBox || !openBtn || !preview) return;

            function renderPreview() {
                var selected = select.value;
                if (!selected || !mediaMap[selected]) {
                    preview.innerHTML = 'Ingen bild vald';
                    return;
                }
                var item = mediaMap[selected];
                if (item.isImage && item.url) {
                    preview.innerHTML = '<img src=\"' + item.url + '\" alt=\"' + (item.title || '') + '\">';
                } else {
                    preview.innerHTML = item.title || 'Vald fil';
                }
            }

            function setActive() {
                var selected = select.value;
                var buttons = grid.querySelectorAll('.media-library-item');
                for (var i = 0; i < buttons.length; i++) {
                    if (buttons[i].getAttribute('data-media-id') === selected) {
                        buttons[i].classList.add('active');
                    } else {
                        buttons[i].classList.remove('active');
                    }
                }
                renderPreview();
            }
            grid.addEventListener('click', function(e) {
                var btn = e.target.closest('.media-library-item');
                if (!btn) return;
                if (btn.getAttribute('data-media-action') === 'upload') {
                    uploadBox.style.display = 'block';
                    return;
                }
                select.value = btn.getAttribute('data-media-id');
                panel.style.display = 'none';
                setActive();
            });
            openBtn.addEventListener('click', function() {
                panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
                uploadBox.style.display = 'none';
            });
            select.addEventListener('change', setActive);
            setActive();
        })();
        </script>\n";

        return $out;
    }

    private function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');

        if ($filename === "") {
            $filename = 'file-' . date('YmdHis');
        }

        return $filename;
    }

    private function ensureUniqueFilename($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $candidate = $filename;
        $i = 1;

        while (file_exists($this->uploadDirAbsolute . $candidate)) {
            $suffix = '_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            $candidate = $name . $suffix . ($ext !== "" ? '.' . $ext : '');
            $i++;
        }

        return $candidate;
    }

    private function normalizeTags($tags)
    {
        if (is_array($tags)) {
            $arr = $tags;
        } else {
            $arr = explode(',', (string)$tags);
        }

        $out = array();
        foreach ($arr as $tag) {
            $tag = trim((string)$tag);
            if ($tag !== "") {
                $out[] = $tag;
            }
        }

        return array_values(array_unique($out));
    }

    private function getAllowedExtensions()
    {
        if (isset($GLOBALS['CONFIG']['uploadType']) && is_array($GLOBALS['CONFIG']['uploadType']) && count($GLOBALS['CONFIG']['uploadType']) > 0) {
            return array_values(array_unique(array_map('strtolower', $GLOBALS['CONFIG']['uploadType'])));
        }
        return $this->defaultUploadTypes;
    }

    private function isAllowedExtension($extension)
    {
        if ($extension === "") {
            return false;
        }
        return in_array(strtolower((string)$extension), $this->getAllowedExtensions(), true);
    }

    private function isThumbnailFilename($filename)
    {
        return preg_match('/_thumbnail\\.[a-z0-9]+$/i', (string)$filename) === 1;
    }

    private function resolveThumbnailUrl($item)
    {
        if (!isset($item['relative_path'])) {
            return "";
        }

        $relativePath = str_replace('\\', '/', $item['relative_path']);
        $ext = strtolower((string)pathinfo($relativePath, PATHINFO_EXTENSION));
        if (!$this->isImageExtension($ext)) {
            return "";
        }

        $thumbRelativePath = $this->buildThumbnailRelativePath($relativePath);
        $thumbAbsolutePath = ROOTPATH . $thumbRelativePath;
        if (!is_file($thumbAbsolutePath)) {
            $sourceAbsolute = ROOTPATH . $relativePath;
            $this->createThumbnail($sourceAbsolute, $thumbAbsolutePath, 80, 80, $ext);
        }

        if (is_file($thumbAbsolutePath)) {
            return rtrim(URLBASE, '/') . '/' . $thumbRelativePath;
        }

        return "";
    }

    private function buildThumbnailRelativePath($relativePath)
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $ext = pathinfo($relativePath, PATHINFO_EXTENSION);
        $dir = pathinfo($relativePath, PATHINFO_DIRNAME);
        $name = pathinfo($relativePath, PATHINFO_FILENAME);
        $thumbName = $name . '_thumbnail' . ($ext !== "" ? '.' . $ext : '');
        if ($dir === '.' || $dir === '') {
            return $thumbName;
        }
        return rtrim($dir, '/') . '/' . $thumbName;
    }

    private function isImageExtension($ext)
    {
        return in_array(strtolower((string)$ext), array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'), true);
    }

    private function createThumbnail($sourcePath, $targetPath, $maxW = 80, $maxH = 80, $ext = '')
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        if (!is_file($sourcePath)) {
            return false;
        }

        // Added the possibility to change standard size for thumbnails by defining CONFIG mediaThumbnailSize
        $maxW = isset($GLOBALS['CONFIG']['thumbnailsize']['x']) ? (int)$GLOBALS['CONFIG']['thumbnailsize']['x'] : $maxW;
        $maxH = isset($GLOBALS['CONFIG']['thumbnailsize']['y']) ? (int)$GLOBALS['CONFIG']['thumbnailsize']['y'] : $maxH;

        $ext = strtolower((string)$ext);
        if ($ext === '' || !$this->isImageExtension($ext)) {
            $ext = strtolower((string)pathinfo($sourcePath, PATHINFO_EXTENSION));
        }

        $createFn = null;
        $saveFn = null;
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $createFn = 'imagecreatefromjpeg';
            $saveFn = 'imagejpeg';
        } elseif ($ext === 'png') {
            $createFn = 'imagecreatefrompng';
            $saveFn = 'imagepng';
        } elseif ($ext === 'gif') {
            $createFn = 'imagecreatefromgif';
            $saveFn = 'imagegif';
        } elseif ($ext === 'bmp' && function_exists('imagecreatefrombmp')) {
            $createFn = 'imagecreatefrombmp';
            $saveFn = function ($img, $path) {
                return imagebmp($img, $path);
            };
        } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $createFn = 'imagecreatefromwebp';
            $saveFn = function ($img, $path) {
                return imagewebp($img, $path);
            };
        } else {
            return false;
        }

        if (!function_exists($createFn)) {
            return false;
        }

        $src = @$createFn($sourcePath);
        if (!$src) {
            return false;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            // imagedestroy($src);
            return false;
        }

        $scale = min($maxW / $srcW, $maxH / $srcH);
        $dstW = max(1, (int)floor($srcW * $scale));
        $dstH = max(1, (int)floor($srcH * $scale));
        $dstX = (int)floor(($maxW - $dstW) / 2);
        $dstY = (int)floor(($maxH - $dstH) / 2);

        $thumb = imagecreatetruecolor($maxW, $maxH);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefilledrectangle($thumb, 0, 0, $maxW, $maxH, $transparent);

        imagecopyresampled($thumb, $src, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $ok = false;
        if (is_string($saveFn) && function_exists($saveFn)) {
            if ($saveFn === 'imagejpeg') {
                $ok = $saveFn($thumb, $targetPath, 85);
            } else {
                $ok = $saveFn($thumb, $targetPath);
            }
        } elseif (is_callable($saveFn)) {
            $ok = $saveFn($thumb, $targetPath);
        }

        // imagedestroy($thumb);
        // imagedestroy($src);
        return $ok;
    }

    private function readMeta()
    {
        if (!is_file($this->metaFile)) {
            return array();
        }

        $raw = file_get_contents($this->metaFile);
        if ($raw === false || trim($raw) === '') {
            return array();
        }

        $arr = json_decode($raw, true);
        if (!is_array($arr)) {
            return array();
        }

        return $arr;
    }

    private function collectFiles($recursive = true)
    {
        $files = array();

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->uploadDirAbsolute, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }
                if ($fileInfo->getFilename() === '.media-library.json') {
                    continue;
                }
                if ($this->isThumbnailFilename($fileInfo->getFilename())) {
                    continue;
                }
                $files[] = $fileInfo->getPathname();
            }
        } else {
            $list = scandir($this->uploadDirAbsolute);
            if (!is_array($list)) {
                return array();
            }
            foreach ($list as $file) {
                if ($file === '.' || $file === '..' || $file === '.media-library.json') {
                    continue;
                }
                if ($this->isThumbnailFilename($file)) {
                    continue;
                }
                $fullpath = $this->uploadDirAbsolute . $file;
                if (is_file($fullpath)) {
                    $files[] = $fullpath;
                }
            }
        }

        return $files;
    }

    private function writeMeta($items)
    {
        $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new Exception("Kunde inte serialisera media metadata.");
        }

        if (file_put_contents($this->metaFile, $json, LOCK_EX) === false) {
            throw new Exception("Kunde inte spara media metadata.");
        }
    }
}
