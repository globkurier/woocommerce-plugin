<?php

namespace udigroup_globkurier;

class GlobKurierAddons extends GlobKurier{
	public function productAddons( $params ){
		$params = apply_filters( 'globkurier_get_products_addons_params', $params );
		do_action( 'globkurier_before_get_products_addons', $params );
		
		$requiredFields = [
			'productId',
			'width',
			'height',
			'length',
			'weight',
			'quantity',
			'senderCountryId',
			'receiverCountryId',
			'senderPostCode',
			'receiverPostCode',
		];
		
		foreach( $requiredFields as $requiredField ){
			if( ! isset( $params[ $requiredField ] ) ){
				$this->handleError( 'Brak Wymaganego Pola: ' . $requiredField );
			}
		}
		
		$function = 'product/addons';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse( $function, $token, $params, $method );
	
		foreach( $response[ 'data' ][ 'addons' ] as &$addon ){
			if( ! empty( $addon[ 'description' ] ) ){
				$addon[ 'tooltip' ] = wc_help_tip( $addon[ 'description' ], TRUE );
			}
		}
		
		$addons = apply_filters( 'globkurier_product_addons', $response[ 'data' ] );
		
		do_action( 'globkurier_after_get_products', $response, $addons );
		
		return $addons;
	}
	
	private function addonExtraFields( $data ){
		
		$cod = $this->settings[ 'default' ][ 'parcel' ][ 'cod' ][ 'account' ];
		
		parse_str( $data[ 'address' ], $addressData );
		
		$senderNames = mb_split( ' ', $addressData[ 'globkurier-sender-name' ] ?? '' );
		
		$min = ! ( empty( $data[ 'minValue' ] ) ) ? $data[ 'minValue' ] : '';
		$max = ! ( empty( $data[ 'maxValue' ] ) ) ? $data[ 'maxValue' ] : '';
		
		return [
			'INSURANCE'              => [
				[
					'name'     => 'insurance-value',
					'label'    => __( 'Wartość ubezpieczenia', 'globkurier' ),
					'type'     => 'number',
					'min'      => $min,
					'max'      => $max,
					'required' => TRUE,
				]
			],
			'CASH_ON_DELIVERY'       => [
				[
					'name'     => 'cod-value',
					'label'    => __( 'Wartość pobrania', 'globkurier' ),
					'type'     => 'number',
					'min'      => $min,
					'max'      => $max,
					'required' => TRUE,
				],
				[
					'name'     => 'cod-bankAccountNumber',
					'label'    => __( 'Numer konta bankowego', 'globkurier' ),
					'value'    => $cod[ 'number' ],
					'help'     => __( 'Numer konta w formacie IBAN ( PL29109015199335376470438408 )', 'globkurier' ),
					'required' => TRUE,
				],
				[
					'name'     => 'cod-name',
					'label'    => __( 'Nazwa klienta do przelewu', 'globkurier' ),
					'value'    => $cod[ 'name' ],
					'required' => TRUE,
				],
				[
					'name'     => 'cod-addressLine1',
					'label'    => __( 'Adres do przelewu linia 1', 'globkurier' ),
					'value'    => $cod[ 'owner' ],
					'required' => TRUE,
				],
				[
					'name'     => 'cod-addressLine2',
					'label'    => __( 'Adres do przelewu linia 2', 'globkurier' ),
					'value'    => $cod[ 'address' ],
					'required' => FALSE,
				],
			],
			'RETURN_OF_DOCUMENTS'    => [
				[
					'name'     => 'rod-content',
					'label'    => __( 'Typ dokumentów', 'globkurier' ),
					'required' => FALSE,
				],
				[
					'name'     => 'rod-quantity',
					'label'    => __( 'Ilość dokumentów', 'globkurier' ),
					'type'     => 'number',
					'min'      => $min,
					'max'      => $max,
					'required' => FALSE,
				],
				[
					'name'     => 'rod-description',
					'label'    => __( 'Opis', 'globkurier' ),
					'required' => FALSE,
				],
			],
			'SENDER_WAYBILL_ADDRESS' => [
				[
					'name'     => 'swa-name',
					'label'    => __( 'Imię', 'globkurier' ),
					'value'    => $senderNames[ 0 ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-surname',
					'label'    => __( 'Nazwisko', 'globkurier' ),
					'value'    => $senderNames[ 1 ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-street',
					'label'    => __( 'Ulica', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-street' ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-houseNumber',
					'label'    => __( 'Numer domu', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-home' ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-apartmentNumber',
					'label'    => __( 'Numer mieszkania', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-flat' ] ?? '',
					'required' => FALSE,
				],
				[
					'name'     => 'swa-city',
					'label'    => __( 'Miasto', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-city' ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-postCode',
					'label'    => __( 'Kod pocztowy', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-postal' ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-countryId',
					'label'    => __( 'Kraj', 'globkurier' ),
					'type'     => 'select',
					'options'  => $this->countries()->getDropdown( $addressData[ 'globkurier-sender-country' ] ?? null ),
					'required' => TRUE,
				],
				[
					'name'     => 'swa-phone',
					'label'    => __( 'Numer telefonu', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-contact-phone' ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-email',
					'label'    => __( 'Email', 'globkurier' ),
					'value'    => $addressData[ 'globkurier-sender-email' ] ?? '',
					'required' => TRUE,
				],
				[
					'name'     => 'swa-type',
					'label'    => __( 'Typ', 'globkurier' ),
					'type'     => 'select',
					'options'  => implode( '', [
						'<option value="PERSON" selected>Osoba indywidualna</option>',
						'<option value="COMPANY">Firma</option>',
					] ),
					'required' => TRUE,
				],
			]
		];
	}
	
	public function getAddonExtraFields( $data ){
		$extraFields = $this->addonExtraFields( $data );
		
		
		return $extraFields[ $data[ 'category' ] ] ?? [];
	}
	
}