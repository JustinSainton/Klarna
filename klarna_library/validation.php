<?php
/**
 *  Copyright 2010 KLARNA AB. All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without modification, are
 *  permitted provided that the following conditions are met:
 *
 *     1. Redistributions of source code must retain the above copyright notice, this list of
 *        conditions and the following disclaimer.
 *
 *     2. Redistributions in binary form must reproduce the above copyright notice, this list
 *        of conditions and the following disclaimer in the documentation and/or other materials
 *        provided with the distribution.
 *
 *  THIS SOFTWARE IS PROVIDED BY KLARNA AB "AS IS" AND ANY EXPRESS OR IMPLIED
 *  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 *  FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL KLARNA AB OR
 *  CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 *  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 *  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 *  ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  The views and conclusions contained in the software and documentation are those of the
 *  authors and should not be interpreted as representing official policies, either expressed
 *  or implied, of KLARNA AB.
 *
 */


/**
 * Helper Functions for client side validation
 *
 * @version 0.1.0
 * @package klarna_payment_module
 */


/**
 * Check Pno for Sweden
 *
 * Format for Pno:
 * YYYYMMDDCNNNN, C = -|+, YYYYMMDDNNNN, YYMMDDCNNNN, YYMMDDNNNN, length 10-13
 *
 * @param string $pno    Personal number for Sweden
 *
 * @return bool
 */
function validate_pno_se($pno) {
    $result = false;

    $pno = str_replace(array('/','.',"_",',',':',';', ' ', '-', '\\'), "", $pno);
    //Pno has 10-13 characters
    if (check_length_ge($pno, 10) && check_length_le($pno, 13)) {
        $result = true;
    }
    return $result;
}

/**
 * Check Pno for Norway
 *
 * Format for Pno:
 * DDMMYYIIIKK, DDMMYY-IIIKK, DDMMYYYYIIIKK, DDMMYYYY-IIIKK ("fodelsenummer" or "D-nummer") length = 11-14
 *
 * @param string $pno    Personal number for Noway
 *
 * @return bool
 */
function validate_pno_nor($pno) {
    $result = false;

    $pno = str_replace(array('/','.',"_",',',':',';', ' ', '-', '\\'), "", $pno);
    //Pno has 11-14 characters
    if (check_length_ge($pno, 11) && check_length_le($pno, 14)) {
        $result = true;
    }
    return $result;
}

/**
 * Check Pno for Denmark
 *
 * Format for Pno:
 * DDMMYYNNNG, G = gender, odd/even for men/women
 *
 * @param string $pno    Personal number for Denmark
 *
 * @return bool
 */
function validate_pno_den($pno) {
    $result = false;

    $pno = str_replace(array('/','.',"_",',',':',';', ' ', '-', '\\'), "", $pno);
    //Pno has 10 characters
    if (check_length_e($pno, 10)) {
        $d = substr($pno, 0, 2);
        $m = substr($pno, 2, 2);
        $y = substr($pno, 4, 2);

        //Check DDMMYY from Pno
        if(checkdate($m, $d, $y)) {
            $result = true;
        }
    }
    return $result;
}

/**
 * Check Pno for Finland
 *
 * Format for Pno:
 * DDMMYYCIIIT, DDMMYYIIIT
 * C = century, '+' = 1800, '-' = 1900 och 'A' = 2000.
 * I = 0-9
 * T = 0-9, A-F, H, J, K-N, P, R-Y
 *
 * @param string $pno    Personal number for Finland
 *
 *
 * @return bool
 */
function validate_pno_fin($pno) {
    $result = false;

    $pno = str_replace(array('/','.',"_",',',':',';', ' ', '-', '\\'), "", $pno);
    //Pno has 10-11 characters
    if (check_length_ge($pno, 10) || check_length_le($pno, 11)) {
        $d = substr($pno, 0, 2);
        $m = substr($pno, 2, 2);
        $y = substr($pno, 4, 2);
        
        if($y == '00')
            $y = '2000';

        //Check DDMMYY from Pno
        if(checkdate($m, $d, $y)) {
            $result = true;
        }
    }
    return $result;
}

/**
 * Check Pno for Germany
 *
 * Format for Pno:
 * DDMMYYYY length 8
 *
 * @param string $dob    Personal number for Germany
 *
 *
 * @return bool
 */
function validate_pno_de($dob) {
    $result = false;

    $dob = str_replace(array('/','.',"_",',',':',';', ' ', '-', '\\'), "", $dob);
    //Pno has 8 characters
    if (check_length_e($dob, 8)) {
        $d = substr($dob, 0, 2);
        $m = substr($dob, 2, 2);
        $y = substr($dob, 4, 4);

        //Check DDMMYY from Pno
        if(checkdate($m, $d, $y)) {
            $result = true;
        }
    }
    return $result;
}

/**
 * Check Pno for Netherlands
 *
 * Format for Pno: DDMMYYYY length 8
 *
 * @param string $dob    Personal number for Netherlands
 *
 * @return bool
 */
function validate_pno_nl($dob) {
    //NL has same Pno format like Germany
    return validate_pno_de($dob);
}

/**
 * Check Cellphone for Sweden
 *
 * Format for Cellphone:
 * 7-13 digits (without country prefix +46)
 * Starts with 010, 070, 072, 073, 076
 * Leading zero is removed when using country prefix
 *
 * @param string  $phone    Cellphone for Sweden
 *
 * @return bool
 */
function validate_phone_se($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+46', '0', $phone);

    //Cellphone number 7 - 13 digits
    if (check_length_ge($phone, 7) && check_length_le($phone, 13)) {
        $array_of_chars = array('010', '070', '072', '073', '076');
        $result = check_substr_chars($phone, $array_of_chars, 0, 3);
    }
    return $result;
}

/**
 * Check Home phone for Sweden
 *
 * Format for Home phone:
 * 7-13 digits (without country prefix +46)
 * Starts with 011-019, 02-06, 071, 075, 077-079, 08-09
 * leading zero is removed when using country prefix *
 * @param string  $phone    Home phone for Sweden
 *
 * @return bool
 */
function validate_phone2_se($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+46', '0', $phone);

    //Cellphone number 7 - 13 digits
    if (check_length_ge($phone, 7) && check_length_le($phone, 13)) {
        // check the first 3-digits
        $array_of_chars = array(
                '011', '012', '013', '014', '015', '016', '017', '018', '019',
                '077', '078', '079'
        );

        $result = check_substr_chars($phone, $array_of_chars, 0, 3);

        //If the above check didn't match check the first 2-digits
        if(!$result) {
            // check the first 2-digits
            $array_of_chars = array(
                    '02', '03', '04', '05', '06', '08', '09'
            );
            $result = check_substr_chars($phone, $array_of_chars, 0, 2);
        }

    }
    return $result;
}

/**
 * Check Cellphone for Norway
 *
 * Format for Cellphone:
 * 8 digits (without country prefix +47)
 * Starts with 40-49 or 90-99
 *
 * @param string  $phone    Cellphone for Norway
 *
 * @return bool
 */
function validate_phone_nor($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+47', '', $phone);

    //Cellphone number 8 digits
    if (check_length_e($phone, 8)) {
        $array_of_chars = array('40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99');
        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    return $result;
}

/**
 * Home phone for Norway
 *
 * Format for Home phone:
 * 8 digits (without country prefix +47)
 * starts with 2, 3, 5-8
 *
 * @param string  $phone    Home phone  for Norway
 *
 * @return bool
 */
function validate_phone2_nor($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+47', '', $phone);

    //Cellphone number 8 digits
    if (check_length_e($phone, 8)) {
        $array_of_chars = array('2', '3', '5', '6', '7', '8');
        $result = check_substr_chars($phone, $array_of_chars, 0, 1);
    }
    return $result;
}

/**
 * Check Cellphone for Denmark
 *
 * Format for Cellphone:
 * 8 digits (without country prefix +45)
 * Starts with 20-29, 30, 31, 40-42, 50-53, 60, 61 71 or 81
 *
 * @param string  $phone    Cellphone for Denmark
 *
 * @return bool
 */
function validate_phone_den($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+45', '', $phone);

    //Cellphone number 8 - 12 digits
    if (check_length_ge($phone, 8) && check_length_le($phone, 12)) {
        $array_of_chars = array('20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '40', '41', '42', '50', '51', '52', '53', '60', '61', '71', '81');
        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    return $result;
}

/**
 * Check Home phone for Denmark
 *
 * Format for home phone:
 * 8 digits (without country prefix +45)
 * starts with 32-39, 43-49, 54-59, 62-69, 72-79, 82-89, 96-99
 *
 * @param string  $phone    Home phone for Denmark
 *
 * @return bool
 */
function validate_phone2_den($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+45', '', $phone);

    //Cellphone number 8 digits
    if (check_length_e($phone, 8)) {
        $array_of_chars = array(
                '32', '33', '34', '35', '36', '37', '38', '39',
                '43', '44', '45', '46', '47', '48', '49',
                '54', '55', '56', '57', '58', '59',
                '62', '63', '64', '65', '66', '67', '68', '69',
                '72', '73', '74', '75', '76', '77', '78', '79',
                '82', '83', '84', '85', '86', '87', '88', '89',
                '96', '97', '98', '99'
        );

        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    return $result;
}

/**
 * Check Cellphone for Finland
 *
 * Format for Cellphone:
 * 6 - 12 digits (without country prefix +358)
 * starts with 040-049 or 050
 * leading zero is removed when using country prefix
 *
 * @param string  $phone    Cellphone for Finland
 *
 * @return bool
 */
function validate_phone_fin($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+358', '0', $phone);

    //Home phone number 6 - 12 digits
    if (check_length_ge($phone, 6) && check_length_le($phone, 12)) {
        $array_of_chars = array(
                '040', '041', '042', '043', '044', '045', '046', '047', '048', '049', '050'
        );
        $result = check_substr_chars($phone, $array_of_chars, 0, 3);
    }
    return $result;
}

/**
 * Check Home phone for Finland
 *
 * Format for Home phone:
 * variable length - accept 5-12 digits (without country prefix +358)
 * starts with 01-03, 05-09
 * Leading zero is removed when using country prefix
 *
 * @param string  $phone    Home phone for Finland
 *
 * @return bool
 */
function validate_phone2_fin($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+358', '0', $phone);

    //Home phone number 5 - 12 digits
    if (check_length_ge($phone, 5) && check_length_le($phone, 12)) {
        $array_of_chars = array('01', '02', '03', '05', '06', '07', '08', '09');
        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    return $result;
}

/**
 * Check Cellphone for Germany
 *
 * Format for Cellphone:
 * 8 - 12 digits (without country prefix +49)
 * starts with 015-017
 * leading zero is removed when using country prefix
 *
 * @param string  $phone    Cellphone for Germany
 *
 * @return bool
 */
function validate_phone_de($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+49', '0', $phone);

    //Cellphone number 8 - 12 digits
    if (check_length_ge($phone, 8) && check_length_le($phone, 12)) {
        $array_of_chars = array('015', '016','017');
        $result = check_substr_chars($phone, $array_of_chars, 0, 3);
    }
    //Allow empty Cellphone
    if (empty($phone)) {
        $result = true;
    }

    return $result;
}

/**
 * Check Home phone for Germany
 *
 * Format for Home phone:
 * 4-12 digits (without country prefix +49)
 * starts with 01-09
 * leading zero is removed when using country prefix
 *
 * @param string  $phone    Home phone for Germany
 *
 * @return bool
 */
function validate_phone2_de($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+49', '0', $phone);

    // Home phone number 4 - 12 digits
    if (check_length_ge($phone, 4) && check_length_le($phone, 12)) {
        $array_of_chars = array('01', '02', '03', '04', '05', '06', '07', '08', '09');
        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    return $result;
}

/**
 * Check Cellphone for Netherlands
 *
 * Format Cellphone:
 * 9-10 digits (without prefix +31 or 0031)
 * starts with 06
 * leading zero is removed when using country prefix
 *
 * @param string  $phone    Cellphone for Netherlands
 *
 * @return bool
 */
function validate_phone_nl($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+31', '0', $phone);

    //Check if phone number has prefix 0031 and replace the it with 0
    $array_of_chars = array('0031');
    if(check_substr_chars($phone, $array_of_chars, 0, 4)) {
        $phone = substr_replace($phone, '0', 0, 4);
    }

    //Cellphone number 9 - 10 digits
    if (check_length_ge($phone, 9) && check_length_le($phone, 10)) {
        $array_of_chars = array('06');
        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    //Allow empty Cellphone
    if (empty($phone)) {
        $result = true;
    }

    return $result;
}

/**
 * Check Home phone for Netherlands
 *
 * Format Home phone:
 * variable length - accept 9-10 digits (without country prefix +31, 0031)
 * starts with 01-05, 07-08
 * Leading zero is removed when using country prefix
 *
 * @param string  $phone    Home phone for Netherlands
 *
 *
 * @return bool
 */
function validate_phone2_nl($phone) {
    $result = false;

    //Remove bad characters
    $phone = strip_bad_characters($phone);
    //If there is a prefix replace this with 0
    $phone = str_replace('+31', '0', $phone);

    //Check if phone number has prefix 0031 and replace the it with 0
    $array_of_chars = array('0031');
    if(check_substr_chars($phone, $array_of_chars, 0, 4)) {
        $phone = substr_replace($phone, '0', 0, 4);
    }

    // Home phone number 9 - 10 digits
    if (check_length_ge($phone, 9) && check_length_le($phone, 10)) {
        $array_of_chars = array('01', '02', '03', '04', '05', '07', '08');
        $result = check_substr_chars($phone, $array_of_chars, 0, 2);
    }
    return $result;
}

/**
 * Check Gender
 *
 * @param string $gender    Check male(1) or female(0)
 *
 * @return bool
 */
function validate_gender($gender) {
    if($gender == '0' || $gender == '1') {
        return true;
    } else {
        return false;
    }
}

/**
 * Check String
 *
 * @param string $string    Check length of a string and is it a string
 *
 * @return bool
 */
function validate_string($string) {
    $result = false;
    $string = trim($string);

    if (is_string($string) && check_length_ge($string, 2)) {
        $result = true;
    }
    return $result;
}

/**
 * Extended String check
 *
 * @param string $string    Check lenght of a string, can also contain numbers
 *
 * @return bool
 */
function validate_string_ext($string) {
    $result = false;
    $string = trim($string);

    if (check_length_ge($string, 2)) {
        $result = true;
    }
    return $result;
}

/**
 * Check house number
 *
 * @param string $string    House number has minimum one character
 *
 * @return bool
 */
function validate_house_number($house_number) {
    $result = false;
    $house_number = trim($house_number);

    if (!empty($house_number) && check_length_ge($house_number, 1)) {
        $result = true;
    }
    return $result;
}

/**
 * Check ZIP code
 *
 * @param string $zipcode    ZIP code has minimum one character
 *
 * @return bool
 */
function validate_zipcode($zipcode) {
    $result = false;

    //Minimum one character
    $house_number = trim($house_number);
    if (!empty($zipcode) && check_length_ge($zipcode, 1)) {
        $result = true;
    }
    return $result;
}

/**
 * Check E-Mail
 *
 * Regular Expression:
 * regExp: '^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z0-9-][a-zA-Z0-9-]+)+$'
 *
 * @param string $email        Check E-Mail with regular expression
 *
 * @return bool
 */
function validate_email($email) {
    //Regular expression for the email check
    $exp = "/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z0-9-][a-zA-Z0-9-]+)+$/";
    return check_regexp($email, $exp);
}

/**
 * Check Salary
 *
 * @param string  $salary    Salary is significant digit
 *
 * @return bool
 */
function validate_salary ($salary) {
    //Salary is significant digit
    if(ctype_digit($salary) && $salary != '0') {
        return true;
    } else {
        return false;
    }
}

/**
 * Strip and allow bad characters
 *
 * @param string  $string    Strip and allow bad characters
 *
 * @return string $string    String without bad characters
 */
function strip_bad_characters ($string) {
    //Remove bad characters
    $string = str_replace(' ', '', $string);
    $string = str_replace('/', '', $string);
    $string = str_replace('\\', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace(',', '', $string);
    $string = str_replace('.', '', $string);
    $string = str_replace('-', '', $string);
    $string = str_replace('#', '', $string);
    $string = str_replace('#', '', $string);
    $string = str_replace('&', '', $string);
    $string = str_replace('*', '', $string);
    return $string;
}

/**
 * Check if a list of character is in a field
 *
 * @param string  $field            Search String
 *
 * @param array  $array_of_chars    Array witht characters for searching
 *
 * @param string  $start            Start parameter
 *
 * @param string  $start            Length parameter
 *
 * @return bool
 */
function check_substr_chars ($field, $array_of_chars, $start, $lenght) {
    foreach ($array_of_chars as $value) {
        $sub_str = substr($field, $start, $lenght);
        if ($value == $sub_str)
            return true;
    }
    return false;
}

/**
 * Searches a string for matches to the regular expression
 *
 * @param string  $field        Search string
 *
 * @param array   $exp            Regular expression
 *
 * @return bool
 */
function check_regexp ($field, $exp) {
    if(preg_match($exp, $field)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check lengh of a string, is greater than or equal
 *
 * @param string  $field        Search string
 *
 * @param string  $start        Length parameter
 *
 * @return bool
 */
function check_length_ge ($field, $lenght) {
    if(strlen($field) >= $lenght) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check lengh of a string, is less than or equal
 *
 * @param string  $field        Search string
 *
 * @param string  $start        Length parameter
 *
 * @return bool
 */
function check_length_le ($field, $lenght) {
    if(strlen($field) <= $lenght) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check lengh of a string, is equal
 *
 * @param string  $field        Search string
 *
 * @param string  $start        Length parameter
 *
 * @return bool
 */
function check_length_e ($field, $lenght) {
    if(strlen($field) == $lenght) {
        return true;
    } else {
        return false;
    }
}
?>