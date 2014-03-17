<?php

namespace Pim\Bundle\DataGridBundle\Datasource\ResultRecord\MongoDbOdm;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Pim\Bundle\DataGridBundle\Datasource\ResultRecord\HydratorInterface;
use Pim\Bundle\CatalogBundle\Doctrine\MongoDBODM\ProductQueryUtility;

/**
 * Hydrate results of Doctrine MongoDB query as ResultRecord array
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Hydrator implements HydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate($queryBuilder, $options)
    {
        $locale = $options['locale_code'];
        $scope  = $options['scope_code'];
        $config     = $options['attributes_configuration'];

        $query = $queryBuilder->hydrate(false)->getQuery();
        $results = $query->execute();

        $attributes = [];
        foreach ($config as $attributeConf) {
            $attributes[$attributeConf['id']]= $attributeConf;
        }

        $rows       = [];
        foreach ($results as $result) {
            $result = $this->prepareStaticData($result, $locale);
            $result = $this->prepareValuesData($result, $attributes, $locale, $scope);
            $result = $this->prepareLinkedData($result, $locale, $scope);

            $rows[] = new ResultRecord($result);
        }

        return $rows;
    }

    /**
     * @param array  $result
     * @param string $locale
     *
     * @return array
     */
    protected function prepareStaticData(array $result, $locale)
    {
        $result['id']= $result['_id']->__toString();
        unset($result['_id']);
        $result['dataLocale']= $locale;

        return $result;
    }

    /**
     * @param array  $result
     * @param array  $attributes
     * @param string $locale
     * @param string $scope
     *
     * @return array
     */
    protected function prepareValuesData(array $result, array $attributes, $locale, $scope)
    {
        if (isset($result['values'])) {
            foreach ($result['values'] as $value) {
                $filterValueLocale = isset($value['locale']) && ($value['locale'] !== $locale);
                $filterValueScope = isset($value['scope']) && ($value['scope'] !== $scope);
                $attributeId = $value['attribute'];

                if (!$filterValueLocale && !$filterValueScope and isset($attributes[$attributeId])) {
                    $attribute = $attributes[$attributeId];
                    $attributeCode = $attribute['code'];
                    $value['attribute']= $attribute;
                    $result[$attribute['code']]= $value;
                    $normalizedData = $result['normalizedData'];

                    $fromNormData = array('pim_catalog_simpleselect', 'pim_catalog_multiselect');
                    if (in_array($attribute['attributeType'], $fromNormData)) {
                        $fieldCode = ProductQueryUtility::getNormalizedValueField(
                            $attributeCode,
                            $attribute['localizable'],
                            $attribute['scopable'],
                            $locale,
                            $scope
                        );
                        $backendType = $attribute['backendType'];
                        $result[$attributeCode][$backendType]= $normalizedData[$fieldCode];
                    }
                }

            }
            unset($result['values']);
        }

        return $result;
    }

    /**
     * @param array  $result
     * @param string $locale
     * @param string $scope
     *
     * @return array
     */
    protected function prepareLinkedData(array $result, $locale, $scope)
    {
        $normalizedData = $result['normalizedData'];

        $completenessCode = $scope.'-'.$locale;
        if (isset($normalizedData['completenesses'][$completenessCode])) {
            $result['ratio']= $normalizedData['completenesses'][$completenessCode];
        } else {
            $result['ratio'] = '-';
        }
        unset($result['normalizedData']['completenesses']);

        if (isset($normalizedData['family'])) {
            $family = $normalizedData['family'];
            $result['familyLabel']= isset($family['label'][$locale]) ?
                $family['label'][$locale] : '['.$family['code'].']';
        } else {
            $result['familyLabel'] = '-';
        }
        unset($result['normalizedData']['family']);

        return $result;
    }
}