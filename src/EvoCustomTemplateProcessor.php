<?php

namespace EvolutionCMS\EvoCustom;

use EvolutionCMS\Interfaces\CoreInterface;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\TemplateProcessor;

class EvoCustomTemplateProcessor extends TemplateProcessor
{
    public function getBladeDocumentContent()
    {
        $template = false;
        $doc      = $this->core->documentObject;
        if ($doc['template'] == 0) {
            return $template;
        }
        $templateAlias = SiteTemplate::select('templatealias')->find($doc['template'])->templatealias;
        switch (true) {
            case $this->core['view']->exists('tpl-' . $doc['template'] . '_doc-' . $doc['id']):
                $template = 'tpl-' . $doc['template'] . '_doc-' . $doc['id'];
                break;
            case $this->core['view']->exists('doc-' . $doc['id']):
                $template = 'doc-' . $doc['id'];
                break;
            case $this->core['view']->exists('tpl-' . $doc['template']):
                $template = 'tpl-' . $doc['template'];
                break;
            case $this->core['view']->exists($templateAlias):
                $baseClassName = $this->core->getConfig('EvoCustomTemplateNamespace') . 'BaseController';
                if (class_exists($baseClassName)) { //Проверяем есть ли Base класс
                    $classArray = explode('.', $templateAlias);
                    $classArray = array_map(
                        function ($item) {
                            return $this->setPsrClassNames($item);
                        },
                        $classArray
                    );
                    $classViewPart = implode('.', $classArray);
                    $className = str_replace('.', '\\', $classViewPart);
                    $className =
                        $this->core->getConfig('EvoCustomTemplateNamespace') . ucfirst($className) . 'Controller';
                    if (!class_exists(
                        $className
                    )) { //Проверяем есть ли контроллер по алиасу, если нет, то помещаем Base
                        $className = $baseClassName;
                    }
                    $customClass = new $className();
                }
                $template = $templateAlias;
                break;
            default:
                $content = $doc['template'] ? $this->core->documentContent : $doc['content'];
                if (!$content) {
                    $content = $doc['content'];
                }
                if (strpos($content, '@FILE:') === 0) {
                    $template = str_replace('@FILE:', '', trim($content));
                    if (!$this->core['view']->exists($template)) {
                        $this->core->documentObject['template'] = 0;
                        $this->core->documentContent            = $doc['content'];
                    }
                }
        }

        return $template;
    }

    /**
     * @param string $templateAlias
     * @return string
     */
    private function setPsrClassNames(string $templateAlias): string
    {
        $explodedTplName = explode('_', $templateAlias);
        $explodedTplName = array_map(
            function ($item) {
                return ucfirst(trim($item));
            },
            $explodedTplName
        );

        return join($explodedTplName);
    }
}