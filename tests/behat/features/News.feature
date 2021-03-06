@d8 @api @clonedb
Feature: News feature

  @wysiwyg
  Scenario: News creation
    Given I am logged in with credentials:
      | username | admin     |
      | password | propeople |
    When I am on "node/add/news"
    Then I should see "Create News" in the "h1" element
    When I fill "edit-title-0-value" with "AT News feature 1"
    And I choose "October 13, 2018" in "edit-field-content-date-0-value-date" datepicker
    Then I check that "edit-field-content-date-0-value-date" datepicker contains "October 13, 2018" date
    When I click on "a[href='#edit-group-s']"
    Then I should see an "select#edit-langcode-0-value" element
    When I fill "edit-field-author-0-value" with "News AT author"
    And I work with elements in "#edit-body-wrapper" region
    And I press the "Edit summary" button
    And I fill "edit-body-0-summary" with "Summary for News in News feature"
    And I work with "Body" WYSIWYG editor
    And I type "Body text in News AT feature" in WYSIWYG editor
    And I checkout to whole page
    And I click on "a[href='#edit-group-content']"
    And I work with elements in "div#edit-field-content-paragraph" region
    And I click on "li.dropbutton-toggle"
    And I click on "Add Content text"
    And I wait until AJAX is finished
    And I checkout to whole page
    And I work with elements in "div#edit-field-content-paragraph-wrapper" region
    And I press the "Edit summary" button
    And I fill in "field_content_paragraph[0][subform][field_content_text][0][summary]" with "Summary of the Content text paragraph"
    And I work with "Content text" WYSIWYG editor
    And I type "Content text in News AT feature" in WYSIWYG editor
    And I checkout to whole page
    And I click on "a[href='#edit-group-bottom']"
    And I select "Interested in joining the P4H Network?" from "edit-field-join-block"
    And I select "Health Financing" from "edit-field-category"
    And I select "World Bank" from "edit-field-organization"
    And I click on "Save and publish"
    Then I should see "News AT News feature 1 has been created."
    And I should see "13 Oct. 2018" in the "time" element
    And I should see "By: News AT author" in the "span.author" element
    And I should see "AT News feature 1" in the "h1.page-title" element
    And I should see "Summary of the Content text paragraph"
    And I should see "Content text in News AT feature"
    And I should see "Interested in joining the P4H Network?"
    #Next - checking Event appearance on the News&Events page
    And I am on "user/logout"
    When I am on "/news-and-events"
    Then I should see "News and events" in the "h1" element
    And I should see an "div[data-drupal-selector='edit-gid']" element
    And I should see an "select#edit-year" element
    And I should see an "select#edit-month" element
    And I should see an "a[href='/en/join-the-network']" element
    When I click on "div[data-drupal-selector='edit-category']"
    And I check the box "Health Financing"
    And I wait until AJAX is finished
    Then I should see "AT News feature 1"
    When I click on "div[data-drupal-selector='edit-organization']"
    And I check the box "World Bank"
    And I wait until AJAX is finished
    Then I should see "AT News feature 1"

  @wysiwyg
  Scenario: News edition
    Given I am logged in with credentials:
      | username | admin     |
      | password | propeople |
    When I am on "admin/content"
    And fill "edit-title" with "AT News feature 1"
    And click on "edit-submit-content"
    And click on "li.edit.dropbutton-action > a"
    Then I should see "Edit News AT News feature 1" in the "h1" element
    When I fill "edit-title-0-value" with "AT News feature 1 edited"
    And I choose "October 14, 2018" in "edit-field-content-date-0-value-date" datepicker
    Then I check that "edit-field-content-date-0-value-date" datepicker contains "October 14, 2018" date
    When I click on "a[href='#edit-group-s']"
    When I fill "edit-field-author-0-value" with "News AT author edited"
    And I work with elements in "#edit-body-wrapper" region
    And I work with "Body" WYSIWYG editor
    And I fill "Body text in News AT feature edited" in WYSIWYG editor
    And I checkout to whole page
    And I click on "a[href='#edit-group-content']"
    And I work with elements in "div#edit-field-content-paragraph-wrapper" region
    And I fill in "field_content_paragraph[0][subform][field_content_text][0][summary]" with "Summary of the Content text paragraph edited"
    And I work with "Content text" WYSIWYG editor
    And I fill "Content text in News AT feature edited" in WYSIWYG editor
    And I checkout to whole page
    And I click on "li.publish"
    Then I should see "News AT News feature 1 edited has been updated."
    And I am on "admin/content"
    When fill "edit-title" with "AT News feature 1 edited"
    And click on "edit-submit-content"
    And I wait for AJAX to finish
    And I click "AT News feature 1 edited"
    Then I should see "14 Oct. 2018" in the "time" element
    And I should see "By: News AT author edited" in the "span.author" element
    And I should see "AT News feature 1 edited" in the "h1.page-title" element
    And I should see "Summary of the Content text paragraph edited"
    And I should see "Content text in News AT feature edited"