@paying_for_order_with_klix
Feature: Paying with Klix during checkout
    In order to buy products
    As a Customer
    I want to be able to pay with Klix

    Background:
        Given the store operates on a single channel in "United States"
        And there is a user "romans.davidko@whitedigital.eu" identified by "password123"
        And the store has a payment method "Klix" with a code "Klix" and Klix Checkout gateway
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for free
        And I am logged in as "romans.davidko@whitedigital.eu"

    @ui
    Scenario: Successful payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Klix" payment method
        When I confirm my order with Klix payment
        And I sign in to Klix and pay successfully
        Then I should be notified that my payment has been completed

    @ui
    Scenario: Cancelling the payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Klix" payment method
        When I confirm my order with Klix payment
        And I cancel my Klix payment
        Then I should be notified that my payment has been cancelled
        And I should be able to pay again

    @ui
    Scenario: Retrying the payment with success
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Klix" payment method
        And I have confirmed my order with Klix payment
        But I have cancelled Klix payment
        When I try to pay again with Klix payment
        And I sign in to Klix and pay successfully
        Then I should be notified that my payment has been completed
        And I should see the thank you page

    @ui
    Scenario: Retrying the payment and failing
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Klix" payment method
        And I have confirmed my order with Klix payment
        But I have cancelled Klix payment
        When I try to pay again with Klix payment
        And I cancel my Klix payment
        Then I should be notified that my payment has been cancelled
        And I should be able to pay again
