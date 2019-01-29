<?php

  function parsePromYml($file){

    //Пром файл
    $xmlstr = file_get_contents($file);
    $xmlprom = new SimpleXMLElement($xmlstr);
    $xmlstrr = '<?xml version="1.0" encoding="utf-8"?>
              <!DOCTYPE yml_catalog SYSTEM "shops.dtd">
              <yml_catalog date="2018-08-21 09:25">
                <shop>
                  <currencies>
                    <currency id="USD" rate="'.$xmlprom->currency[2]->attributes()->rate.'"/>
                    <currency id="KZT" rate="1"/>
                    <currency id="RUR" rate="'.$xmlprom->currency[1]->attributes()->rate.'"/>
                    <currency id="BYN" rate="1"/>
                    <currency id="UAH" rate="1"/>
                    <currency id="EUR" rate="'.$xmlprom->currency[0]->attributes()->rate.'"/>
                  </currencies>
                  <categories>
                  </categories>
                <offers>
                </offers>
              </shop>
              </yml_catalog>
              ';
    $xml = new SimpleXMLElement($xmlstrr);
    $category = array();
    $i=0;
    // Вытаскивание категорий из файла Пром
    for ($i=0; $i < count($xmlprom->catalog->category)-1; $i++) { 
      $category[$i] = array('name' => $xmlprom->catalog->category[$i], 'attributes' => $xmlprom->catalog->category[$i]->attributes());
      $category[$i]['attributes']['id'] = $category[$i]['attributes']['id'] + 1; 
      $category[$i]['attributes']['parentId'] = $category[$i]['attributes']['parentId'] + 1; 
    }

    $i = 0;
   
    //Вытаскивание товаров из файла Пром
    $ofersss = array();
    foreach ($xmlprom->items->item as $ofer => $ofers) {
      $ofersss[$i][] = array('name' => $ofers->name);
      $ofersss[$i][] = array('categoryId' => $ofers->categoryId);
      $ofersss[$i][] = array('price' => $ofers->price);
      $ofersss[$i][] = array('currencyId' => $ofers->currencyId);
      $ofersss[$i][] = array('image' => $ofers->image);
      $ofersss[$i][] = array('vendor' => $ofers->vendor);
      $ofersss[$i][] = array('vendorCode' => $ofers->vendorCode);
      $ofersss[$i][] = array('description' => $ofers->description);
      $ofersss[$i][] = array('available' => $ofers->available);
        for ($j=0; $j < count($ofers->param); $j++) {
            $ofersss[$i][] = array("param" => $ofers->param[$j]);
        }
      $atr_id[] = $ofers->attributes()->id;
      $i++;
    }
    // Добавление категорий
    for ($i=0; $i < count($category); $i++) { 
      $xml->shop->categories->category[$i] = $category[$i]['name'];
      $xml->shop->categories->category[$i]->addAttribute('id', $category[$i]['attributes']['id']);
      if ($category[$i]['attributes']['parentId'] != NULL){
          $xml->shop->categories->category[$i]->addAttribute('parentId', $category[$i]['attributes']['parentId']);
      }
    }
    // Формирование оферов (товаров)
    $param = array();
    for ($i=0; $i < count($ofersss); $i++) { 
        $names[$i] = $ofersss[$i][0]['name'];
        $categoryId[$i] = $ofersss[$i][1]['categoryId'] + 1;
        $price[$i] = $ofersss[$i][2]['price'];
        $currencyId[$i] = $ofersss[$i][3]['currencyId'];
        $image[$i] = $ofersss[$i][4]['image'];
        
        $im = explode(':', $image[$i]);
        if ($im[0] != 'https'){
            $im[0] = $im[0].'s';
            $image[$i] = implode(':',$im);
        }
        
        $vendor[$i] = $ofersss[$i][5]['vendor'];
        $vendorCode[$i] = $ofersss[$i][6]['vendorCode'];
        $description[$i] = $ofersss[$i][7]['description'];
        $available[$i] = $ofersss[$i][8]['available'];
        for ($j=9; $j<count($ofersss[$i]); $j++){
          $param["$i"][] = $ofersss[$i][$j]['param'];
  
        }
    }
    // Формирование XML файла
    for ($i=0; $i < count($ofersss)-1; $i++) {
        // Создание офера(товара)
        $offer_new[$i] = $xml->shop->offers->addChild('offer','');
        $offer_new[$i]->addAttribute('available', 'true');
        $offer_new[$i]->addAttribute('id', $atr_id[$i]);
        // Наполнение офера 
        $offer_new[$i]->AddChild('name', htmlspecialchars($names[$i]));
        $offer_new[$i]->AddChild('categoryId', htmlspecialchars($categoryId[$i]));
        $offer_new[$i]->AddChild('price', htmlspecialchars($price[$i]));
        $offer_new[$i]->AddChild('currencyId', htmlspecialchars($currencyId[$i]));
        $offer_new[$i]->AddChild('picture', $image[$i]);
        $offer_new[$i]->AddChild('pickup', 'true');
        $offer_new[$i]->AddChild('vendor', htmlspecialchars($vendor[$i]));
        $offer_new[$i]->AddChild('vendorCode', $vendorCode[$i]);
        $offer_new[$i]->AddChild('description', htmlspecialchars($description[$i]));
        //Параметры у товара
        $count_param = count($param["$i"]);
        for ($j = 0; $j < $count_param; $j++) {
            $param_ofer = $offer_new[$i]->AddChild('param', htmlspecialchars($param["$i"][$j][0]));
            $param_ofer->addAttribute('name', $param["$i"][$j]->attributes()->name);
        }
        $offer_new[$i]->AddChild('available', $available[$i]);
    }
    // Получение курса с файла
    foreach ($xml->shop->currencies->currency as $key) {
      $kurs[] = $key->attributes()->rate;
    }
    // Конвертация
    foreach ($xml->shop->offers->offer as $item) {
      if ($item->currencyId == 'USD'){
          $item->price = (floatval($item->price) * floatval($kurs[0]));
      }elseif($item->currencyId == 'KZT'){
          $item->price = (floatval($item->price) * floatval($kurs[1]));
      }elseif($item->currencyId == 'RUB'){
          $item->price = (floatval($item->price) * floatval($kurs[2]));
      }elseif($item->currencyId == 'BYN'){
          $item->price = (floatval($item->price) * floatval($kurs[3]));
      }elseif($item->currencyId == 'UAH'){
          $item->price = (floatval($item->price) * floatval($kurs[4]));
      }elseif($item->currencyId == 'EUR'){
          $item->price = (floatval($item->price) * floatval($kurs[5]));
      }
      $item->currencyId = 'UAH';

    }

    //var_dump($xml);
    // var_dump($file);
    $xml->asXML($file);
  }
