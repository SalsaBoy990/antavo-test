<?php

/** 
 * Implementation of Simple Payment Form Validation,
 * API exchange valutes from HUF to EUR.
 * Author: András Gulácsi (@SalsaBoy990) 2020-06-21
 */
class SimplePaymentForm
{
  // Store cc card number
  private $ccNumber = "";
  // Store month as MM
  private $month = "";
  // Store year as YY
  private $year = "";
  // Store amount to pay in HUF
  private $amountToPay = "";

  // Min and max values for the amount to pay
  const MIN = 1;
  const MAX = 1000000;

  // Error messages
  public $amountNumberErr = "";
  public $amountLimitsErr = "";
  public $ccNumberErr = "";
  public $ccNumberValidityErr = "";
  public $yearErr = "";
  public $monthErr = "";
  public $validDateErr = "";


  // store error messages
  public $errorStackAmount = array();
  public $errorStackCard = array();
  public $errorStackDate = array();


  function __construct($ccNumber = "", $month = "", $year = "", $amount = null)
  {
    $this->ccNumber = $ccNumber;
    $this->month = $month;
    $this->year = $year;
    $this->amountToPay = $amount;
  }
  function __destruct()
  {
  }

  public function getAmount()
  {
    return $this->amountToPay;
  }

  /**
   * Validate amount to pay.
   * Input should be integer.
   * Between 1 and 1.000.000 HUF
   */
  private function validateAmountToPay($amount)
  {
    if (strpos($amount, ".") || strpos($amount, ",")) {
      $this->amountNumberErr = 'No decimals allowed.<br />';
      array_push($this->errorStackAmount, $this->amountNumberErr);
    }

    if (preg_match("/^\-[0-9]*$/", $amount)) {
      $this->amountNumberErr = 'Only positive numbers allowed.<br />';
      array_push($this->errorStackAmount, $this->amountNumberErr);
    }

    if (decoct(octdec($amount)) !== $amount) {
      array_push($this->errorStackAmount, 'Octal numbers are not allowed.<br />');
    }

    if (dechex(hexdec($amount)) !== $amount) {
      array_push($this->errorStackAmount, 'Hexadecimal numbers are not allowed.<br />');
    }

    if (decbin(bindec($amount)) == $amount) {
      array_push($this->errorStackAmount, 'Binary numbers are not allowed.<br />');
    }

    if (intval($amount)) {
      if ($amount < self::MIN || $amount > self::MAX) {
        $this->amountLimitsErr = 'Amount must be between ' . self::MIN . ' and ' . self::MAX . '!<br />';
        // echo $this->amountLimitsErr;
        array_push($this->errorStackAmount, $this->amountLimitsErr);
      }
    } else {
      $this->amountNumberErr = $amount . ' is not an integer.<br />';
      // echo $this->amountNumberErr;
      array_push($this->errorStackAmount, $this->amountNumberErr);
    }

    if (empty($this->errorStackAmount)) {
      $this->amountToPay = $amount;
      return true;
    } else {
      return false;
    }
  }


  /**
   * Validate date
   */
  private function validateValidDate($mm, $yy)
  {
    // convert into number
    $year = intval('20' . $yy);

    if (!preg_match("/^[0-9]{2,2}$/", $mm)) {
      $this->monthErr = 'Only 2 numbers are allowed for month!<br />';
      // echo $this->monthErr;
      array_push($this->errorStackDate, $this->monthErr);
    }

    $month = null;
    switch ($mm) {
      case '01':
        $month = 1;
        break;
      case '02':
        $month = 2;
        break;
      case '03':
        $month = 3;
        break;
      case '04':
        $month = 4;
        break;
      case '05':
        $month = 5;
        break;
      case '06':
        $month = 6;
        break;
      case '07':
        $month = 7;
        break;
      case '08':
        $month = 8;
        break;
      case '09':
        $month = 9;
        break;
      case '10':
        $month = 10;
        break;
      case '11':
        $month = 11;
        break;
      case '12':
        $month = 12;
        break;
      default:
        $this->monthErr = 'Month number is invalid.<br />';
        // echo $this->monthErr;
        array_push($this->errorStackDate, $this->monthErr);
    }


    if (strlen($yy) !== 2) {
      $this->yearErr = $yy . 'Use the last 2 digits for the year.<br />';
      // echo $this->yearErr;
      array_push($this->errorStackDate, $this->yearErr);
    }


    if (!preg_match("/^[0-9]{2,2}$/", $yy)) {
      $this->yearErr = 'Only numbers are allowed for year!<br />';
      // echo $this->yearErr;
      array_push($this->errorStackDate, $this->yearErr);
    }


    $currentYear = intval(date('Y'), 10);
    $currentMonth = intval(date('m'), 10);
    if ($year < $currentYear) {
      $this->yearErr = 'Card is expired! Expired year(s).<br />';
      // echo $this->yearErr;
      array_push($this->errorStackDate, $this->yearErr);
    } else if ($year === $currentYear) {

      if ($month < $currentMonth) {
        $this->validDateErr = 'Card is expired! Expired month(s).<br />';
        // echo $this->validDateErr;
        array_push($this->errorStackDate, $this->validDateErr);
      } else if ($month === $currentMonth) {
        $day = null;
        // Apr, Jun, Sept, Nov have 30 days
        if ($month === 4 || $month === 6 || $month === 9 || $month === 11) {
          $day = 30;
        } else if ($month === 2) {
          // Leap year
          if ((($year % 4 === 0) && ($year % 100 !== 0)) || $year % 400 === 0) {
            $day = 29;
          } else {
            $day = 28;
          }
        } else {
          $day = 31;
        }
        $currentDateTime = date('Y-m-d H:i:s');
        if ($month < 10) {
          $month = '0' . strval($month);
        }
        $cardValidDate = $year . '-' . $month . '-' . $day . ' 23:59:59';
        $seconds = strtotime($cardValidDate) - strtotime($currentDateTime);
        if ($seconds < 0) {
          $this->validDateErr = 'Your card expired this month!<br />';
          // echo $this->validDateErr;
          array_push($this->errorStackDate, $this->validDateErr);
        }
      }
    }

    if (empty($this->errorStackDate)) {
      // initialize properties
      $this->month = $mm;
      $this->year = $yy;
      return true;
    } else {
      return false;
    }
  }

  /**
   * Validate cc card number
   * Card number length must be 16,
   * Only numbers allowed,
   * Must be validated through Luhn's algorithm succesfully
   */
  private function validateCreditCardNumber($card)
  {
    $onlyNumber = true;
    $cardLength = true;
    $
    // Pre-cleaning just for safety
    $card = trim($card); // Remove trailing whitespace
    $card = preg_replace('/\s/', '', $card); // Remove all whitespace

    // Only numbers!
    if (!preg_match("/^[0-9]*$/", $card)) {
      $this->ccNumberErr = 'Your card must contain only numbers. No special chars allowed.<br />';
      // echo $this->ccNumberErr;
      array_push($this->errorStackCard, $this->ccNumberErr);
      $onlyNumber = false;
    }

    if (strlen($card) !== 16) {
      $this->ccNumberErr = 'The card number must be 16 digits long. Your\'s is ' . strlen($card) . '.<br />';
      // echo $this->ccNumberErr;
      array_push($this->errorStackCard, $this->ccNumberErr);
      $cardLength = false;
    }


    if ($onlyNumber === true && $cardLength === true && !$this->cardValidityLuhn($card)) {
      $this->ccNumberValidityErr = 'Your card failed to pass Luhn algorithm.<br />';
      // echo $this->ccNumberValidityErr;
      array_push($this->errorStackCard, $this->ccNumberValidityErr);
    }


    if (empty($this->errorStackCard)) {
      // inizialize property
      $this->ccNumber = $card;
      return true;
    } else {
      return false;
    }
  }

  /**
   * Luhn algorithm to determine the validity of the credit card number
   * @see http://en.wikipedia.org/wiki/Luhn_algorithm
   */
  private function cardValidityLuhn($card)
  {
    $sum = '';
    $num = 0;

    foreach (str_split(strrev((string) $card)) as $i => $d) {
      // if its odd, multiply by 2
      if ($i % 2 !== 0) {
        $num = $d * 2;

        // if $num is > 10, calculate the sum of the digits
        if ($num > 10) {
          $sum .= floor($num / 10) + ($num % 10);
        } else {
          $sum .= $num;
        }
      } else { // use the original number
        $sum .= $d;
      }
    }
    // echo $sum;
    // The sum must end with zero
    return array_sum(str_split($sum)) % 10 === 0;
  }

  public function printErrorStack($inputName)
  {
    switch ($inputName) {
      case 'date':
        echo '<ul>';
        foreach ($this->errorStackDate as $err) {
          echo '<li>' . $err . '</li>';
        }
        echo '</ul>';
        break;
      case 'amount':
        echo '<ul>';
        foreach ($this->errorStackAmount as $err) {
          echo '<li>' . $err . '</li>';
        }
        echo '</ul>';
        break;
      case 'card':
        echo '<ul>';
        foreach ($this->errorStackCard as $err) {
          echo '<li>' . $err . '</li>';
        }
        echo '</ul>';
        break;
      default:
        echo 'A megadott argumentum rossz ("date", "amount" vagy "card" lehet).';
    }
  }

  public function validateUserInput()
  {
    if ($_SERVER['REQUEST_METHOD'] === "POST") {

      $validCard = false;
      $validMonth = false;
      $validYear = false;
      $validAmount = false;

      if (isset($_POST['cc-number']) && !empty($_POST['cc-number'])) {
        if ($this->validateCreditCardNumber($_POST['cc-number'])) {
          $validCard = true;
        }
      }

      if (
        isset($_POST['expiration-month']) &&
        isset($_POST['expiration-year']) &&
        !empty($_POST['expiration-month']) &&
        !empty($_POST['expiration-year']) &&
        $this->validateValidDate($_POST['expiration-month'], $_POST['expiration-year'])
      ) {
        $validMonth = true;
        $validYear = true;
      }

      if (
        isset($_POST['amount-huf']) &&
        !empty($_POST['amount-huf']) &&
        $this->validateAmountToPay($_POST['amount-huf'])
      ) {
        $validAmount = true;
      }

      // If everything is valid return true
      if (
        $validCard &&
        $validMonth &&
        $validYear &&
        $validAmount
      ) {
        return true;
      } else {
        return false;
      }
    }
  }

  public function echoAllProperties()
  {
    echo '<h2>Data stored:</h2>';
    echo 'Card number: ' . $this->ccNumber . '<br />';
    echo 'MM: ' . $this->month . '<br />';
    echo 'YY: ' . $this->year . '<br />';
    echo 'Amount (in HUF): ' . $this->amountToPay . '<br />';
  }

  // Convert the amount from HUF to EUR
  public function convertCurrency()
  {
    $apikey = 'd350ee1ad0f58dfa92c4';

    $from_Currency = urlencode('HUF');
    $to_Currency = urlencode('EUR');
    $query =  "{$from_Currency}_{$to_Currency}";

    // change to the free URL if you're using the free version
    $json = file_get_contents("https://free.currconv.com/api/v7/convert?q={$query}&compact=ultra&apiKey={$apikey}");
    $obj = json_decode($json, true);

    $val = floatval($obj["$query"]);

    // amountToPay stores the amount in HUF
    $total = $val * $this->amountToPay;
    return number_format($total, 2, '.', '');
  }
}

$userPayment = new SimplePaymentForm();
// it will store the converted value in euro
$amountInEuro = null;
// check input, if okay -> convert currency
if ($userPayment->validateUserInput()) {
  $amountInEuro = $userPayment->convertCurrency() . ' €';
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simple Payment Form</title>
  <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    html,
    body {
      width: 100%;
      height: 100%;
      font-size: 16px;
      color: #000;
      font-family: 'Bai Jamjuree', Arial, Helvetica, sans-serif;
      line-height: 140%;
      background: #fff;
      font-weight: 400;
      margin: 0;
      padding: 0;
      text-align: left;
      scroll-behavior: smooth;
    }

    body,
    p,
    blockquote,
    pre,
    figure,
    figcaption {
      margin: 0;
      padding: 0;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      font-weight: 600;
      color: #000;
      margin-bottom: 20px;
      line-height: 1.1;
    }

    h1 a,
    h2 a,
    h3 a,
    h4 a,
    h5 a,
    h6 a {
      text-decoration: underline;
      font-weight: 600;
    }

    input {
      height: 50px;
      margin-bottom: 20px;
      font-family: 'Bai Jamjuree', Arial, Helvetica, sans-serif;
      font-size: 16px;
      font-weight: 500;
      padding-left: 10px;
    }

    input[type="submit"] {
      cursor: pointer;
      text-transform: uppercase;
      font-size: 14px;
      padding-right: 10px;
    }

    input[type="submit"]:hover,
    input[type="submit"]:focus,
    input[type="submit"]:active {
      background-color: #ECCF6B;
    }

    a {
      text-decoration: underline;
      color: #4c46b8;
      transition: color 0.3s ease-in;
    }

    a:focus,
    a:hover {
      color: #252088;
      text-decoration: none;
    }

    a:focus,
    a:focus img {
      outline: #777 dashed 2px;
    }

    .container-max-width {
      max-width: 1440px;
      margin: 0 60px;
    }

    .container {
      display: grid;
      /* grid-template-rows: repeat(1, 600px); */
      grid-template-columns: minmax(300px, 500px) 1fr;
      justify-items: left;
      align-items: left;
    }

    .footer {
      display: grid;
      grid-template-rows: repeat(1, 60px);
      grid-template-columns: 1fr;
      justify-items: left;
      align-items: left;
    }

    @media screen and (max-width: 576px) {
      .container-max-width {
        margin: 0 15px;
      }
    }

    ::-webkit-input-placeholder {
      font-weight: normal;
      text-align: left;
    }

    :-moz-placeholder {
      font-weight: normal;
      text-align: left;
    }

    ::-moz-placeholder {
      font-weight: normal;
      text-align: left;
    }

    :-ms-input-placeholder {
      font-weight: normal;
      text-align: left;
    }

    .errorMsg {
      font-size: 14px;
      font-weight: 500;
      color: #D61438;
      background-color: #eee;
    }
  </style>
</head>

<body>
  <div class="container container-max-width">
    <div class="content">
      <h2>Simple Payment Form</h2>
      <hr style="border-top: 1px solid #ccc; margin-bottom:30px;">
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        <div>
          <label for="cc-number">Credit card number</label><br />
          <div class="errorMsg">
            <?php $userPayment->printErrorStack('card'); ?>
          </div>
          <input style="width: 300px;" type="text" name="cc-number" inputmode="numeric" aria-label="credit card number" placeholder="4242 4242 4242 4242">

        </div>

        <div>
          <span>Expiration date</span>
          <div class="errorMsg" style="width: 240px;">
            <?php $userPayment->printErrorStack('date'); ?>
          </div>
          <div style="display: grid; grid-template-columns: 120px 100px;">
            <div>
              <label for="expiration-month">Month</label><br />
              <input style="width: 100px; margin-right: 10px;" type="text" name="expiration-month" placeholder="MM" inputmode="numeric" aria-label="credit card expiration month">
            </div>
            <div>
              <label for="expiration-year">Year</label>
              <br /><input style="width: 100px;" type="text" name="expiration-year" placeholder="YY" inputmode="numeric" aria-label="credit card expiration year">
            </div>
          </div>
        </div>
        <div>
          <label for="amount-huf">Amount to pay (HUF)</label><br />
          <span class="errorMsg">
            <?php $userPayment->printErrorStack('amount'); ?>
          </span>
          <input type="number" name="amount-huf" placeholder="1" inputmode="numeric" aria-label="amount to pay in HUF">
        </div>
        <input type="submit" value='Send Money!'>
      </form>

      <div><?php $userPayment->echoAllProperties(); ?></div>

      <div>
        <p style="margin-top: 30px; font-weight: 600;">Amount in Euro is: <?php echo $amountInEuro; ?></p>
      </div>

    </div>
    <div></div><!-- Empty on purpose! -->
  </div>
  <footer class="footer container-max-width">
    <p>Created by <a href="https://github.com/SalsaBoy990" target="_blank" rel="noreferrer noopener">András Gulácsi (SalsaBoy990)</a>.</p>
  </footer>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs=" crossorigin="anonymous"></script>
  <script type="text/javascript">
    $(document).ready(function() {

      var ccNumber = document.querySelector('input[name="cc-number"]');
      // enable spacing for credit card number
      ccNumber.addEventListener('keyup', function() {
        var val = ccNumber.value;
        console.log(val);
        var newVal = '';
        val = val.replace(/\s/g, '');
        for (var i = 0; i < val.length; i++) {
          // after every 4 number add a space
          if (i % 4 === 0 && i > 0) {
            newVal = newVal.concat(' ');
          }
          newVal = newVal.concat(val[i]);
        }
        ccNumber.value = newVal;
      })
    })
  </script>

</body>

</html>