<?php

use Bitrix\Main\Loader;

class SSectionSearch extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['CACHE_TYPE'] = $arParams['CACHE_TYPE'] ?? 'N';
        $arParams['CACHE_TIME'] = $arParams['CACHE_TIME'] ?? 3600;
        $arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
        return $arParams;
    }

    public function executeComponent()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('search');

        $this->arResult['SEARCH'] = [];
        $this->arResult['ORIG_QUERY'] = $this->arResult['QUERY'] = htmlspecialcharsEx(trim($this->request->getQuery('q')));

        $langs = CSearchLanguage::GuessLanguage($this->arResult['ORIG_QUERY']);
        if ($langs['from'] != $langs['to']) {
            $this->arResult['QUERY'] = CSearchLanguage::ConvertKeyboardLayout($this->arResult['ORIG_QUERY'], $langs['from'], $langs['to']);
        }

        if (!empty($this->arResult['QUERY'])) {
            $this->arResult['UPPER_QUERY'] = mb_strtoupper($this->arResult['QUERY']);
            $this->arResult['UPPER_ORIG_QUERY'] = mb_strtoupper($this->arResult['ORIG_QUERY']);
            $sectionsIterator = \Bitrix\Iblock\SectionTable::getList([
                'filter' => [
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    [
                        'LOGIC' => 'OR',
                        ['SEARCHABLE_CONTENT' => "%{$this->arResult['UPPER_QUERY']}%"],
                        ['SEARCHABLE_CONTENT' => "%{$this->arResult['UPPER_ORIG_QUERY']}%"],
                    ],
                ],
                'select' => ['ID']
            ]);

            while ($section = $sectionsIterator->fetch()) {
                $this->arResult['SEARCH'][] = $section;
            }
        }

        $this->includeComponentTemplate();
        return array_column($this->arResult['SEARCH'], 'ID');
    }
}
