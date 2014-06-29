<?php
	/**
 	 * @package image
 	 *
 	 * @author Камайкин Владимир Анатольевич <kamaikin@gmail.com>
 	 *
 	 * @version 0.1
 	 * @since since 2013-01-11
 	 */
	class TImage{
		/**
		 * Ресурс изображения
		 */
		private $img_resource;
		/**
		 * Способ которым будем обрабатывать изображение
		 */
		private $imagick_on_gd = FALSE;
		/**
		 * Путь к обрабатываемому изображению.
		 */
		private $img_patch = '';
		/**
		 * И нформация о принятом изображении
		 */
		private $img_info = FALSE;
		/**
		 * Принудительная установка используемого драйвера обработки изображения
		 */
		private $img_driver = FALSE;
		public function __construct($patch=''){
			if ($patch!='') {
				$this->fotoLoad($patch);
			}
			//print_r($this->imagick_on_gd);
		}

		public function fotoLoad($patch){
			// Выбираем куда будем делать загрузку....
			if (!$this->imagick_on_gd) {
				$this->sposobSelect();
			}
			//	Сохраняем в переменную путь к файлу с которым работаем.
			$this->img_patch = $patch;
			//	Получаем информацию о изображении
			//	Эта функция не требует библиотеки GD image.
			//	Возвращает массив из 4 элементов. 
			//	Индекс 0 содержит ширину/width изображения в пикселах. 
			//	Индекс 1 содержит высоту/height. 
			//	Индекс 2 это флаг, указывающий тип изображения.1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP, 
			//	7 = TIFF(байтовый порядок intel), 8 = TIFF(байтовый порядок motorola), 9 = JPC, 10 = JP2, 11 = JPX.
			$this->img_info = GetImageSize($patch);
			//print_r($this->img_info);
			return $this;
		}

		/**
		 *	Метод изменяет размер изображения...
		 *	@param $width - Ширина изображения которое должно получится (int)
		 *	@param $height - Высота изображения которое должно быть на выходе (int)
		 *	@param $type - Тип выполняемого изменения размера работет только если казаны и ширина и высота изображеия 
		 *	если какой то из параметров передан как "0" то автоматически маштабируется по второму....
		 *	      width - Маштабировать по ширине (высота игнорируется)
		 *	      height - Маштабировать по высоте (ширина игнорируется)
		 *	      tough - Жесткое маштобирование (вписать в указанные ширину и высоту)
		 *	@param $increase - Увеличивать или нет если исходное изображение меньше затребованного.
		 *
		 *	@return 
		 */
		public function imageResize($width=0, $height=0, $type='width', $increase=FALSE){
			//	Просчитваем реальные а не переданные размеры фотографии....
			$new_size = $this->newImgeSize($width, $height, $type);
			if ($new_size!=array()) {
				if ($this->imagick_on_gd=='GD') {
					$this->gdImageResize($new_size[0], $new_size[1]);
				}
				if ($this->imagick_on_gd=='Imagick') {
					$this->iImageResize($new_size[0], $new_size[1]);
				}
			}
			return $this;
		}
		/**
		 * Сохраняет изображение с которым работал класс
		 *
		 */
		public function imageSave($patch='', $type='png'){
			///print_r($patch);exit;
			if ($patch=='') {
				$patch=$this->img_patch;
			}
			if ($this->imagick_on_gd=='GD') {
				if ($type=='jpg'){
					imagejpeg($this->img_resource, $patch, 75);
				}else if ($type=='gif'){
					imagegif($this->img_resource, $patch);
				}else if ($type=='png'){
					imagepng($this->img_resource, $patch);
				}else{
					imagepng($this->img_resource, $patch);
				}
			}
			if ($this->imagick_on_gd=='Imagick') {
				$this->img_resource->setFormat($type);
				$this->img_resource->writeImage($patch);
			}
			return $this;
		}
		/**
		 * Выводим в браузер изображение с переачей соответсвующего mime-type
		 *
		 */
		public function imageView($type='png'){
			if ($this->imagick_on_gd=='GD') {
				if ($type=='jpg'){
					header("Content-type: image/jpeg");
					imagejpeg($this->img_resource);
				}else if ($type=='gif'){
					header("Content-type: image/gif");
					imagegif($this->img_resource);
				}else if ($type=='png'){
					header("Content-type: image/png");
					imagepng($this->img_resource);
				}else{
					header("Content-type: image/png");
					imagepng($this->img_resource);
				}
			}
			if ($this->imagick_on_gd=='Imagick') {
				$this->img_resource->setFormat($type);
				if ($type=='jpg'){
					header("Content-type: image/jpeg");
				}else if ($type=='gif'){
					header("Content-type: image/gif");
				}else if ($type=='png'){
					header("Content-type: image/png");
				}else{
					header("Content-type: image/png");
				}
				echo $this->img_resource->getImagesBlob();
			}
			return $this;
		}

		/**
		 * Жесткое определение используемого драйвера
		 *
		 */
		public function imageDriver($type=''){
			if ($type='gd') {
				$this->img_driver='GD';
			}
			if ($type='imagemagick') {
				$this->img_driver='Imagick';
			}
			return $this;
		}

		/**
		 *	Возвращаем ресурс изображения. Нужно например для установки водяного знака например...
		 */
		public function getImageResourse(){
			if (!$this->img_resource) {
				$this->setImageResourse();
			}
			return $this->img_resource;
		}
		/**
		 *	Разбираем переданные преметры измнения размера, и возвращаем реально возможные.
		 *
		 *	@param $width Запрос ширины
		 *	@param $height Запрос высоты
		 *	@param $type тип расчтов...
		 *
		 *	@return array() Массив с параметрами рассчитанными по нужным формулам
		 */
		private function newImgeSize($width, $height, $type){
			if ($width!=0) {
				//	Ширина не равна нулю
				if ($height!=0) {
					//	Передана и ширина и высота....
					if ($type=='tough') {
						//	Возвращаем переданные параметры и не паримся...
						return array($width, $height);
					} elseif ($type=='width') {
						//	Маштабируем по ширине, высота игнорируется
						$ratioWidth = $this->img_info[0]/$width;
        				$destHeight = $this->img_info[1]/$ratioWidth;
        				return array($width, (int)$destHeight);
					} else {
						//	Маштабируем по высоте, ширина игнорируется
						$ratioHeight = $this->img_info[1]/$height;
        				$destWidth = $this->img_info[0]/$ratioHeight;
        				return array((int)$destWidth, $height);
					}
					
				}else{
					//	Высота равна нулю Маштабируем по ширине
					$ratioWidth = $this->img_info[0]/$width;
    				$destHeight = $this->img_info[1]/$ratioWidth;
    				return array($width, (int)$destHeight);
				}
			}elseif ($height!=0) {
				//print_r($this->img_info);
				//	Здесь ширина выставлена в ноль... а высота нет, маштабируем по высоте...
				$ratioHeight = $this->img_info[1]/$height;
				$destWidth = $this->img_info[0]/$ratioHeight;
				return array((int)$destWidth, $height);
			}else{
				//	Оба размера равны нулю.... ничего не делаем))))
				return array();
			}
		}

		/**
		 *	Ма штабируем изображение с использованием библиотеки ImageMagick
		 *
		 *	@return void
		 */
		private function iImageResize($width=0, $height=0){
			//	Получаем объект ImageMagick с изображением
			$this->setImageResourse();
			$this->img_resource->thumbnailImage($width, $height);
			if ($width < 300){
				$this->img_resource->sharpenImage(4, 1);
			}
			$this->img_info[0] = $width;
			$this->img_info[1] = $height;
			//print_r($this->img_resource);
		}

		/**
		 *	Ма штабируем изображение с использованием библиотеки GD
		 *
		 *	@return void
		 */
		private function gdImageResize($width=0, $height=0){
			//	Получаем ресурс изображения
			$this->setImageResourse();
			$image_p = @imagecreatetruecolor($width, $height);
			imagecopyresampled($image_p, $this->img_resource, 0, 0, 0, 0, $width, $height, $this->img_info[0], $this->img_info[1]);
			$this->img_resource = $image_p;
			$this->img_info[0] = $width;
			$this->img_info[1] = $height;
		}
		/**
		 *	Создаем ресурс изображения в зависимости от типа изображения
		 *
		 *	@return void
		 */
		private function setImageResourse(){
			if ($this->imagick_on_gd=='GD') {
				if ($this->img_info[2]==2){
					$this->img_resource = imagecreatefromjpeg($this->img_patch);
				}else if ($size[2]==3){
					$this->img_resource = imagecreatefrompng($this->img_patch);
				}else if ($size[2]==1){
					$this->img_resource = imagecreatefromgif($this->img_patch);
				}else if ($size[2]==6){
					$this->img_resource = imagecreatefromwbmp($this->img_patch);
				}
			}
			if ($this->imagick_on_gd=='Imagick') {
				$this->img_resource = new Imagick();
				$this->img_resource->readImage($this->img_patch);
			}
		}

		/**
		 *	Определяем какой именно драйвер будет использоваться для работы с изображением
		 *
		 *	@return void
		 */
		private function sposobSelect(){
			//	Определяем способ которым будем работать с изображением.
			if ($this->img_driver) {
				//	Если драйер жестко указан при вызове класса то его и используем...
				$this->imagick_on_gd=$this->img_driver;
			} else {
				//	Если есть в системе ImageMagick то с помощью него, если нету то через GD
				if (class_exists('Imagick')) {
					$this->imagick_on_gd = 'Imagick';
				} else {
					$this->imagick_on_gd = 'GD';
				}
			}
		}
	}