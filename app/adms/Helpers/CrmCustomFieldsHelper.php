<?php

namespace App\adms\Helpers;

/**
 * Helper para renderizar campos customizáveis do CRM
 * 
 * @package App\adms\Helpers
 * @author Rafael Mendes
 */
class CrmCustomFieldsHelper
{
    /**
     * Renderizar campos customizáveis em um formulário
     * 
     * @param array $fields Array de campos customizados (do repository)
     * @param array $values Array de valores já salvos (field_name => value)
     * @param string $prefix Prefixo para os nomes dos campos (ex: 'custom_field_')
     * @return string HTML dos campos
     */
    public static function renderFields(array $fields, array $values = [], string $prefix = 'custom_field_'): string
    {
        if (empty($fields)) {
            return '';
        }

        $html = '<div class="row mb-3">';
        $html .= '<div class="col-12"><hr><h6 class="text-muted mb-3"><i class="fas fa-cog me-2"></i>Campos Customizáveis</h6></div>';
        $html .= '</div>';

        foreach ($fields as $field) {
            $fieldId = $field['id'];
            $fieldName = $prefix . $fieldId;
            $fieldLabel = htmlspecialchars($field['field_label']);
            $fieldType = $field['field_type'];
            $isRequired = !empty($field['is_required']);
            // Buscar valor usando field_name como chave (formato do repository)
            $currentValue = $values[$field['field_name']] ?? '';

            // Determinar tamanho da coluna baseado no tipo
            $colSize = self::getColumnSize($fieldType);

            $html .= '<div class="row mb-3">';
            $html .= '<div class="' . $colSize . '">';
            $html .= '<label class="form-label">';
            $html .= $fieldLabel;
            if ($isRequired) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';

            // Renderizar campo baseado no tipo
            switch ($fieldType) {
                case 'text':
                    $html .= self::renderTextInput($fieldName, $currentValue, $isRequired);
                    break;
                
                case 'textarea':
                    $html .= self::renderTextarea($fieldName, $currentValue, $isRequired);
                    break;
                
                case 'number':
                    $html .= self::renderNumberInput($fieldName, $currentValue, $isRequired);
                    break;
                
                case 'date':
                    $html .= self::renderDateInput($fieldName, $currentValue, $isRequired);
                    break;
                
                case 'select':
                    $html .= self::renderSelect($fieldName, $field['field_options'], $currentValue, $isRequired);
                    break;
                
                case 'checkbox':
                    $html .= self::renderCheckbox($fieldName, $field['field_options'], $currentValue, $isRequired);
                    break;
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Renderizar input de texto
     */
    private static function renderTextInput(string $name, string $value, bool $required): string
    {
        $requiredAttr = $required ? 'required' : '';
        return sprintf(
            '<input type="text" name="%s" class="form-control" value="%s" %s>',
            htmlspecialchars($name),
            htmlspecialchars($value),
            $requiredAttr
        );
    }

    /**
     * Renderizar textarea
     */
    private static function renderTextarea(string $name, string $value, bool $required): string
    {
        $requiredAttr = $required ? 'required' : '';
        return sprintf(
            '<textarea name="%s" class="form-control" rows="3" %s>%s</textarea>',
            htmlspecialchars($name),
            $requiredAttr,
            htmlspecialchars($value)
        );
    }

    /**
     * Renderizar input numérico
     */
    private static function renderNumberInput(string $name, string $value, bool $required): string
    {
        $requiredAttr = $required ? 'required' : '';
        return sprintf(
            '<input type="number" name="%s" class="form-control" step="0.01" value="%s" %s>',
            htmlspecialchars($name),
            htmlspecialchars($value),
            $requiredAttr
        );
    }

    /**
     * Renderizar input de data
     */
    private static function renderDateInput(string $name, string $value, bool $required): string
    {
        $requiredAttr = $required ? 'required' : '';
        // Converter data do formato brasileiro para formato do input (YYYY-MM-DD)
        $dateValue = '';
        if (!empty($value)) {
            // Se já está no formato correto, usar direto
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $dateValue = $value;
            } else {
                // Tentar converter de formato brasileiro
                $dateParts = explode('/', $value);
                if (count($dateParts) === 3) {
                    $dateValue = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                }
            }
        }
        
        return sprintf(
            '<input type="date" name="%s" class="form-control" value="%s" %s>',
            htmlspecialchars($name),
            htmlspecialchars($dateValue),
            $requiredAttr
        );
    }

    /**
     * Renderizar select
     */
    private static function renderSelect(string $name, ?string $optionsJson, string $currentValue, bool $required): string
    {
        $options = [];
        if (!empty($optionsJson)) {
            $options = json_decode($optionsJson, true) ?? [];
        }

        $requiredAttr = $required ? 'required' : '';
        $html = sprintf('<select name="%s" class="form-select" %s>', htmlspecialchars($name), $requiredAttr);
        $html .= '<option value="">Selecione...</option>';

        foreach ($options as $option) {
            $optionValue = trim($option);
            $selected = ($currentValue === $optionValue) ? 'selected' : '';
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($optionValue),
                $selected,
                htmlspecialchars($optionValue)
            );
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Renderizar checkbox (múltipla escolha)
     */
    private static function renderCheckbox(string $name, ?string $optionsJson, string $currentValue, bool $required): string
    {
        $options = [];
        if (!empty($optionsJson)) {
            $options = json_decode($optionsJson, true) ?? [];
        }

        // Para checkbox, o valor pode ser uma string separada por vírgulas
        $selectedValues = [];
        if (!empty($currentValue)) {
            $selectedValues = array_map('trim', explode(',', $currentValue));
        }

        $html = '<div class="d-flex flex-wrap gap-3">';
        foreach ($options as $option) {
            $optionValue = trim($option);
            $checked = in_array($optionValue, $selectedValues) ? 'checked' : '';
            $fieldId = htmlspecialchars($name . '_' . md5($optionValue));
            
            $html .= '<div class="form-check">';
            $html .= sprintf(
                '<input class="form-check-input" type="checkbox" name="%s[]" value="%s" id="%s" %s>',
                htmlspecialchars($name),
                htmlspecialchars($optionValue),
                $fieldId,
                $checked
            );
            $html .= sprintf(
                '<label class="form-check-label" for="%s">%s</label>',
                $fieldId,
                htmlspecialchars($optionValue)
            );
            $html .= '</div>';
        }
        $html .= '</div>';

        if ($required) {
            $html .= '<small class="text-danger">Pelo menos uma opção deve ser selecionada</small>';
        }

        return $html;
    }

    /**
     * Determinar tamanho da coluna baseado no tipo de campo
     */
    private static function getColumnSize(string $fieldType): string
    {
        // Campos que ocupam toda a linha
        if (in_array($fieldType, ['textarea', 'checkbox'])) {
            return 'col-12';
        }
        
        // Campos menores ocupam metade
        return 'col-md-6';
    }

    /**
     * Exibir campos customizados em modo visualização (não editável)
     * 
     * @param array $fields Array de campos customizados
     * @param array $values Array de valores salvos
     * @return string HTML para exibição
     */
    public static function displayFields(array $fields, array $values = []): string
    {
        if (empty($fields)) {
            return '';
        }

        $html = '<div class="row mb-3">';
        $html .= '<div class="col-12"><hr><h6 class="text-muted mb-3"><i class="fas fa-cog me-2"></i>Campos Customizáveis</h6></div>';
        $html .= '</div>';

        foreach ($fields as $field) {
            $fieldLabel = htmlspecialchars($field['field_label']);
            $fieldType = $field['field_type'];
            $currentValue = $values[$field['field_name']] ?? '';

            $html .= '<div class="row mb-2">';
            $html .= '<div class="col-md-3"><strong>' . $fieldLabel . ':</strong></div>';
            $html .= '<div class="col-md-9">';

            if (empty($currentValue)) {
                $html .= '<span class="text-muted">Não informado</span>';
            } else {
                switch ($fieldType) {
                    case 'checkbox':
                        // Para checkbox, pode ser string separada por vírgulas
                        $valuesArray = array_map('trim', explode(',', $currentValue));
                        $html .= '<span class="badge bg-secondary me-1">' . implode('</span><span class="badge bg-secondary me-1">', array_map('htmlspecialchars', $valuesArray)) . '</span>';
                        break;
                    
                    case 'date':
                        // Formatar data para exibição brasileira
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $currentValue)) {
                            $dateParts = explode('-', $currentValue);
                            $html .= date('d/m/Y', strtotime($currentValue));
                        } else {
                            $html .= htmlspecialchars($currentValue);
                        }
                        break;
                    
                    case 'number':
                        // Formatar número
                        if (is_numeric($currentValue)) {
                            $html .= number_format((float)$currentValue, 2, ',', '.');
                        } else {
                            $html .= htmlspecialchars($currentValue);
                        }
                        break;
                    
                    default:
                        $html .= nl2br(htmlspecialchars($currentValue));
                }
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }
}

