// tests/FormValidationTest.php
<?php
use PHPUnit\Framework\TestCase;
require_once 'Tests\FormValidation.php'; // Adjust the path as needed

class FormValidationTest extends TestCase {

    /**
     * @dataProvider emailProvider
     */
    public function testValidateEmail($email, $expectedResult) {
        $result = FormValidation::validateEmail($email);
        $this->assertEquals($expectedResult, $result);
    }

    public function emailProvider() {
        return [
            ["test@example.com", true], // Valid email
            ["invalid-email", false],   // Invalid email
            ["", false],                // Empty string
            ["email@domain", false],    // Missing TLD
            ["user@sub.domain.com", true], // Valid email with subdomain
            ["@missingusername.com", false], // Missing username
        ];
    }
}
?>
