/**
 * Payable status
 */
CREATE TYPE status_payable AS enum (
	'trash',
	'draft',
	'pending',
	'reversed',
	'refunded',
	'cancelled',
	'cleared'
	);

/**
 * Payable behavior
 *
 * Adds fields:
 * - rec_interval
 * - rec_count
 *
 * Adds constraint:
 * - valid_interval
 *
 * Adds triggers:
 * - {table}_01__check_interval
 */
CREATE OR REPLACE FUNCTION payable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF	NOT constraint_exists(t_name, 'valid_flow')
	THEN
		RAISE EXCEPTION 'Constraint valid_% does not exist on %. Default: %', 'flow', t_name,
		$EXEC$
			CONSTRAINT valid_flow
				CHECK ( NOT ( issue_date IS NULL AND status > 'draft' ) AND
					NOT ( due_date IS NULL AND status > 'draft' ) AND
					NOT ( cleared_date IS NULL AND status = 'cleared' ) )
		$EXEC$;
	END IF;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__sanitize_flow') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		-- Assign default dates if needed
		IF	NEW.issue_date IS NULL AND NEW.status > 'draft'
		THEN
			NEW.issue_date := NOW();
		END IF;
		IF	NEW.due_date IS NULL AND NEW.status > 'draft'
		THEN
			NEW.due_date := NOW();
		END IF;
		IF	NEW.cleared_date IS NULL AND NEW.status = 'cleared'
		THEN
			NEW.cleared_date := NOW();
		END IF;

		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_30__sanitize_flow')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_30__sanitize_flow') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__sanitize_flow') || $EXEC$();
		$EXEC$;
	END IF;
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;


/**
 * Payment type
 */
CREATE TYPE type_payment AS enum (
	'revenue',
	'expense'
	);

/**
 * Payment method
 */
CREATE TYPE method_payment AS enum (
	'paypal',
	'moneybookers',
	'creditcard',
	'check',
	'misc'
	);

/**
 * ISO 4217 Currencies
 */
CREATE TYPE code_currency AS enum (
	'AED', -- United Arab Emirates, Dirhams
	'AFN', -- Afghanistan, Afghanis
	'ALL', -- Albania, Leke
	'AMD', -- Armenia, Drams
	'ANG', -- Netherlands Antilles, Guilders (also called Florins)
	'AOA', -- Angola, Kwanza
	'ARS', -- Argentina, Pesos
	'AUD', -- Australia, Dollars
	'AWG', -- Aruba, Guilders (also called Florins)
	'AZN', -- Azerbaijan, New Manats
	'BAM', -- Bosnia and Herzegovina, Convertible Marka
	'BBD', -- Barbados, Dollars
	'BDT', -- Bangladesh, Taka
	'BGN', -- Bulgaria, Leva
	'BHD', -- Bahrain, Dinars
	'BIF', -- Burundi, Francs
	'BMD', -- Bermuda, Dollars
	'BND', -- Brunei Darussalam, Dollars
	'BOB', -- Bolivia, Bolivianos
	'BRL', -- Brazil, Brazil Real
	'BSD', -- Bahamas, Dollars
	'BTN', -- Bhutan, Ngultrum
	'BWP', -- Botswana, Pulas
	'BYR', -- Belarus, Rubles
	'BZD', -- Belize, Dollars
	'CAD', -- Canada, Dollars
	'CDF', -- Congo/Kinshasa, Congolese Francs
	'CHF', -- Switzerland, Francs
	'CLP', -- Chile, Pesos
	'CNY', -- China, Yuan Renminbi
	'COP', -- Colombia, Pesos
	'CRC', -- Costa Rica, Colones
	'CUP', -- Cuba, Pesos
	'CVE', -- Cape Verde, Escudos
	'CZK', -- Czech Republic, Koruny
	'DJF', -- Djibouti, Francs
	'DKK', -- Denmark, Kroner
	'DOP', -- Dominican Republic, Pesos
	'DZD', -- Algeria, Algeria Dinars
	'EEK', -- Estonia, Krooni
	'EGP', -- Egypt, Pounds
	'ERN', -- Eritrea, Nakfa
	'ETB', -- Ethiopia, Birr
	'EUR', -- Euro Member Countries, Euro
	'FJD', -- Fiji, Dollars
	'FKP', -- Falkland Islands (Malvinas), Pounds
	'GBP', -- United Kingdom, Pounds
	'GEL', -- Georgia, Lari
	'GGP', -- Guernsey, Pounds
	'GHS', -- Ghana, Cedis
	'GIP', -- Gibraltar, Pounds
	'GMD', -- Gambia, Dalasi
	'GNF', -- Guinea, Francs
	'GTQ', -- Guatemala, Quetzales
	'GYD', -- Guyana, Dollars
	'HKD', -- Hong Kong, Dollars
	'HNL', -- Honduras, Lempiras
	'HRK', -- Croatia, Kuna
	'HTG', -- Haiti, Gourdes
	'HUF', -- Hungary, Forint
	'IDR', -- Indonesia, Rupiahs
	'ILS', -- Israel, New Shekels
	'IMP', -- Isle of Man, Pounds
	'INR', -- India, Rupees
	'IQD', -- Iraq, Dinars
	'IRR', -- Iran, Rials
	'ISK', -- Iceland, Kronur
	'JEP', -- Jersey, Pounds
	'JMD', -- Jamaica, Dollars
	'JOD', -- Jordan, Dinars
	'JPY', -- Japan, Yen
	'KES', -- Kenya, Shillings
	'KGS', -- Kyrgyzstan, Soms
	'KHR', -- Cambodia, Riels
	'KMF', -- Comoros, Francs
	'KPW', -- Korea (North), Won
	'KRW', -- Korea (South), Won
	'KWD', -- Kuwait, Dinars
	'KYD', -- Cayman Islands, Dollars
	'KZT', -- Kazakhstan, Tenge
	'LAK', -- Laos, Kips
	'LBP', -- Lebanon, Pounds
	'LKR', -- Sri Lanka, Rupees
	'LRD', -- Liberia, Dollars
	'LSL', -- Lesotho, Maloti
	'LTL', -- Lithuania, Litai
	'LVL', -- Latvia, Lati
	'LYD', -- Libya, Dinars
	'MAD', -- Morocco, Dirhams
	'MDL', -- Moldova, Lei
	'MGA', -- Madagascar, Ariary
	'MKD', -- Macedonia, Denars
	'MMK', -- Myanmar (Burma), Kyats
	'MNT', -- Mongolia, Tugriks
	'MOP', -- Macau, Patacas
	'MRO', -- Mauritania, Ouguiyas
	'MUR', -- Mauritius, Rupees
	'MVR', -- Maldives (Maldive Islands), Rufiyaa
	'MWK', -- Malawi, Kwachas
	'MXN', -- Mexico, Pesos
	'MYR', -- Malaysia, Ringgits
	'MZN', -- Mozambique, Meticais
	'NAD', -- Namibia, Dollars
	'NGN', -- Nigeria, Nairas
	'NIO', -- Nicaragua, Cordobas
	'NOK', -- Norway, Krone
	'NPR', -- Nepal, Nepal Rupees
	'NZD', -- New Zealand, Dollars
	'OMR', -- Oman, Rials
	'PAB', -- Panama, Balboa
	'PEN', -- Peru, Nuevos Soles
	'PGK', -- Papua New Guinea, Kina
	'PHP', -- Philippines, Pesos
	'PKR', -- Pakistan, Rupees
	'PLN', -- Poland, Zlotych
	'PYG', -- Paraguay, Guarani
	'QAR', -- Qatar, Rials
	'RON', -- Romania, New Lei
	'RSD', -- Serbia, Dinars
	'RUB', -- Russia, Rubles
	'RWF', -- Rwanda, Rwanda Francs
	'SAR', -- Saudi Arabia, Riyals
	'SBD', -- Solomon Islands, Dollars
	'SCR', -- Seychelles, Rupees
	'SDG', -- Sudan, Pounds
	'SEK', -- Sweden, Kronor
	'SGD', -- Singapore, Dollars
	'SHP', -- Saint Helena, Pounds
	'SLL', -- Sierra Leone, Leones
	'SOS', -- Somalia, Shillings
	'SPL', -- Seborga, Luigini
	'SRD', -- Suriname, Dollars
	'STD', -- Sao Tome and Principe, Dobras
	'SVC', -- El Salvador, Colones
	'SYP', -- Syria, Pounds
	'SZL', -- Swaziland, Emalangeni
	'THB', -- Thailand, Baht
	'TJS', -- Tajikistan, Somoni
	'TMM', -- Turkmenistan, Manats
	'TND', -- Tunisia, Dinars
	'TOP', -- Tonga, Pa'anga
	'TRY', -- Turkey, New Lira
	'TTD', -- Trinidad and Tobago, Dollars
	'TVD', -- Tuvalu, Tuvalu Dollars
	'TWD', -- Taiwan, New Dollars
	'TZS', -- Tanzania, Shillings
	'UAH', -- Ukraine, Hryvnia
	'UGX', -- Uganda, Shillings
	'USD', -- United States of America, Dollars
	'UYU', -- Uruguay, Pesos
	'UZS', -- Uzbekistan, Sums
	'VEF', -- Venezuela, Bolivares Fuertes
	'VND', -- Viet Nam, Dong
	'VUV', -- Vanuatu, Vatu
	'WST', -- Samoa, Tala
	'XAF', -- Communaute Financiere Africaine BEAC, Francs
	'XAG', -- Silver, Ounces
	'XAU', -- Gold, Ounces
	'XCD', -- East Caribbean Dollars
	'XDR', -- International Monetary Fund (IMF) Special Drawing Rights
	'XOF', -- Communaute Financiere Africaine BCEAO, Francs
	'XPD', -- Palladium Ounces
	'XPF', -- Comptoirs Francais du Pacifique Francs
	'XPT', -- Platinum, Ounces
	'YER', -- Yemen, Rials
	'ZAR', -- South Africa, Rand
	'ZMK', -- Zambia, Kwacha
	'ZWD'  -- Zimbabwe, Zimbabwe Dollars
	);
