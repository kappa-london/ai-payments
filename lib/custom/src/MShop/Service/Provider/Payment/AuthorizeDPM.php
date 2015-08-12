<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package MShop
 * @subpackage Service
 */


/**
 * Payment provider for Authorize.NET DPM.
 *
 * @package MShop
 * @subpackage Service
 */
class MShop_Service_Provider_Payment_AuthorizeDPM
	extends MShop_Service_Provider_Payment_AuthorizeSIM
	implements MShop_Service_Provider_Payment_Interface
{
	private $_feConfig = array(
		'payment.firstname' => array(
			'code' => 'payment.firstname',
			'internalcode'=> 'x_first_name',
			'label'=> 'First name',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false
		),
		'payment.lastname' => array(
			'code' => 'payment.lastname',
			'internalcode'=> 'x_last_name',
			'label'=> 'Last name',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> true
		),
		'payment.cardno' => array(
			'code' => 'payment.cardno',
			'internalcode'=> 'x_card_num',
			'label'=> 'Credit card number',
			'type'=> 'number',
			'internaltype'=> 'integer',
			'default'=> '',
			'required'=> true
		),
		'payment.cvv' => array(
			'code' => 'payment.cvv',
			'internalcode'=> 'x_card_code',
			'label'=> 'Verification number',
			'type'=> 'number',
			'internaltype'=> 'integer',
			'default'=> '',
			'required'=> true
		),
		'payment.expirymonthyear' => array(
			'code' => 'payment.expirymonthyear',
			'internalcode'=> 'x_exp_date',
			'label'=> 'Expiry date',
			'type'=> 'number',
			'internaltype'=> 'integer',
			'default'=> '',
			'required'=> true
		),
		'payment.company' => array(
			'code' => 'payment.company',
			'internalcode'=> 'x_company',
			'label'=> 'Company',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
		'payment.address1' => array(
			'code' => 'payment.address1',
			'internalcode'=> 'x_address',
			'label'=> 'Street',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
		'payment.city' => array(
			'code' => 'payment.city',
			'internalcode'=> 'x_city',
			'label'=> 'City',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
		'payment.postal' => array(
			'code' => 'payment.postal',
			'internalcode'=> 'x_zip',
			'label'=> 'Zip code',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
		'payment.countryid' => array(
			'code' => 'payment.countryid',
			'internalcode'=> 'x_country',
			'label'=> 'Country',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
		'payment.telephone' => array(
			'code' => 'payment.telephone',
			'internalcode'=> 'x_phone',
			'label'=> 'Telephone',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
		'payment.email' => array(
			'code' => 'payment.email',
			'internalcode'=> 'x_email',
			'label'=> 'E-Mail',
			'type'=> 'string',
			'internaltype'=> 'string',
			'default'=> '',
			'required'=> false,
			'public' => false,
		),
	);


	/**
	 * Returns the payment form for entering payment details at the shop site.
	 *
	 * @param MShop_Order_Item_Interface $order Order object
	 * @param array $params Request parameter if available
	 * @return MShop_Common_Item_Helper_Form_Interface Form helper object
	 */
	protected function _getPaymentForm( MShop_Order_Item_Interface $order, array $params )
	{
		$feConfig = $this->_feConfig;
		$form = parent::_getPaymentForm( $order, $params );
		$baseItem = $this->_getOrderBase( $order->getBaseId(), MShop_Order_Manager_Base_Abstract::PARTS_ADDRESS );

		try
		{
			$address = $baseItem->getAddress();

			if( !isset( $params[ $feConfig['payment.firstname']['internalcode'] ] )
				|| $params[ $feConfig['payment.firstname']['internalcode'] ] == ''
			) {
				$feConfig['payment.firstname']['default'] = $address->getFirstname();
			}

			if( !isset( $params[ $feConfig['payment.lastname']['internalcode'] ] )
				|| $params[ $feConfig['payment.lastname']['internalcode'] ] == ''
			) {
				$feConfig['payment.lastname']['default'] = $address->getLastname();
			}

			if( $this->_getValue( 'address' ) )
			{
				$feConfig['payment.address1']['default'] = $address->getAddress1() . ' ' . $address->getAddress2();
				$feConfig['payment.city']['default'] = $address->getCity();
				$feConfig['payment.postal']['default'] = $address->getPostal();
				$feConfig['payment.countryid']['default'] = $address->getCountryId();
				$feConfig['payment.telephone']['default'] = $address->getTelephone();
				$feConfig['payment.company']['default'] = $address->getCompany();
				$feConfig['payment.email']['default'] = $address->getEmail();
			}
		}
		catch( MShop_Order_Exception $e ) { ; } // If address isn't available

		foreach( $feConfig as $key => $config ) {
			$form->setValue( $key, new MW_Common_Criteria_Attribute_Default( $config ) );
		}

		return $form;
	}


	/**
	 * Returns the prefix for the configuration definitions
	 *
	 * @return string Prefix without dot
	 */
	protected function _getConfigPrefix()
	{
		return 'authorizenet';
	}


	/**
	 * Returns the value for the given configuration key
	 *
	 * @param string $key Configuration key name
	 * @param mixed $default Default value if no configuration is found
	 * @return mixed Configuration value
	 */
	protected function _getValue( $key, $default = null )
	{
		switch( $key )
		{
			case 'type': return 'AuthorizeNet_DPM';
			case 'onsite': return true;
		}

		return parent::_getValue( $key, $default );
	}
}