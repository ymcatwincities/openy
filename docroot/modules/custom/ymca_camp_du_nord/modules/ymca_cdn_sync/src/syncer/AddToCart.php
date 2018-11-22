<?php

namespace Drupal\ymca_cdn_sync\syncer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_cdn_sync\SyncException;
use GuzzleHttp\Client;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class AddToCart.
 *
 * @property string $dataServiceUrl
 * @property string $dataServiceUser
 * @property string $dataServicePassword
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
class AddToCart implements AddToCartInterface {

  /**
   * Config name.
   */
  const CONFIG_NAME = 'ymca_cdn_sync.settings';

  /**
   * Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Wrapper.
   *
   * @var \Drupal\ymca_cdn_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Fetcher constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger channel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(Client $client, LoggerChannelInterface $logger, ConfigFactoryInterface $config, WrapperInterface $wrapper, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager) {
    $this->client = $client;
    $this->logger = $logger;
    $this->config = $config;
    $this->wrapper = $wrapper;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Magic method to get properties.
   *
   * @todo Move to lower level to reuse.
   *
   * @param mixed $property
   *   Property name.
   *
   * @return array|mixed|null
   *   Property value.
   */
  public function __get($property) {
    $config = $this->config->get(self::CONFIG_NAME);
    $property_underscore = $this->fromCamelCase($property);

    if (property_exists($this, $property_underscore)) {
      return $this->$property_underscore;
    }

    return $config->get($property_underscore);
  }

  /**
   * Convert CamelCase to underscore.
   *
   * @todo Move to lower level to reuse.
   *
   * @see https://stackoverflow.com/a/1993772/1547435
   *
   * @param string $input
   *   Input.
   *
   * @return string
   *   Output.
   */
  private function fromCamelCase($input) {
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
      $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
    }
    return implode('_', $ret);
  }

  /**
   * {@inheritdoc}
   */
  public function addToCart($user_id, $product_ids) {
    if (empty($user_id)) {
      $this->logger->error('A personify user id was not provided.');
      throw new SyncException('A personify user id was not provided.');
    }
    $this->wrapper->setSourceData([]);
    $product_ids = explode(',', $product_ids);
    $product_id = reset($product_ids);
    if (empty($package = $this->getPackageId($product_id))) {
      $this->logger->error('A package id was not found for provided component id.');
      throw new SyncException('A package id was not found for provided component id.');
    }

    $options = [
      'timeout' => 90,
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];

    // @todo Make this XML from array. Possibly move to config.
    $options['body'] = "<AddListOfProductsToCartInput xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
      <MasterCustomerId>$user_id</MasterCustomerId>
      <SubCustomerId>0</SubCustomerId>
      <PreferredCurrencyCode>USD</PreferredCurrencyCode>
      <AddedOrModifiedBy>$user_id</AddedOrModifiedBy>
      <CLIsCLWorkFlow>true</CLIsCLWorkFlow>
      <CLFlowCompleted xsi:nil=\"true\" />
      <CLMinorAdultMasterCustomerId>$user_id</CLMinorAdultMasterCustomerId>
      <CLMinorAdultSubCustomerId>0</CLMinorAdultSubCustomerId>
      <CLPrimaryGuardianMasterCustomerId />
      <CLPrimaryGuardianSubCustomerId>0</CLPrimaryGuardianSubCustomerId>
      <CLSecondaryGuardianMasterCustomerId />
      <CLSecondaryGuardianSubCustomerId>0</CLSecondaryGuardianSubCustomerId>
      <CLYearsAtCamp />
      <CLSchoolInFall />
      <CLReferFriend1Name />
      <CLReferFriend1Phone />
      <CLReferFriend1Email />
      <CLReferFriend1Address />
      <CLReferFriend2Name />
      <CLReferFriend2Phone />
      <CLReferFriend2Email />
      <CLReferFriend2Address />
      <CLMPHearYMCA />
      <CLTShirtSize />
      <CLSchoolSubCustomerId>0</CLSchoolSubCustomerId>
      <CLCalculateScheduleForMainProduct>true</CLCalculateScheduleForMainProduct>
      <CLMPSearchParameters>{\"&lt;AgeRange&gt;k__BackingField\":\"\",\"&lt;AvailableText&gt;k__BackingField\":\"Available\",\"&lt;BackorderText&gt;k__BackingField\":\"Place Backorder\",\"&lt;ClassFullText&gt;k__BackingField\":\"Class Full\",\"&lt;CurrencyCode&gt;k__BackingField\":\"USD\",\"&lt;ExcludeFullProducts&gt;k__BackingField\":false,\"&lt;FacilityCustomerValue&gt;k__BackingField\":\"\",\"&lt;FacilityMasterCustomerId&gt;k__BackingField\":null,\"&lt;FacilitySubCustomerId&gt;k__BackingField\":null,\"&lt;FillSessionFlag&gt;k__BackingField\":\"Y\",\"&lt;Grade&gt;k__BackingField\":\"1\",\"&lt;GroupByWeekFlag&gt;k__BackingField\":\"N\",\"&lt;INVThresholdText&gt;k__BackingField\":\"Only #TH left!\",\"&lt;LocationCaptionResult&gt;k__BackingField\":\"Held: \",\"&lt;OrgID&gt;k__BackingField\":\"YMCA\",\"&lt;OrgUnitID&gt;k__BackingField\":\"YMCA\",\"&lt;PCKCapacityText&gt;k__BackingField\":\"See Details\",\"&lt;PageNo&gt;k__BackingField\":1,\"&lt;PageSize&gt;k__BackingField\":5,\"&lt;ParticipantMasterCustomerId&gt;k__BackingField\":\"2051432211\",\"&lt;ParticipantSubCustomerId&gt;k__BackingField\":0,\"&lt;PreorderText&gt;k__BackingField\":\"Place Pre-Order\",\"&lt;ProductClassSelectedValue&gt;k__BackingField\":\"MP|MTG\",\"&lt;ProductClass&gt;k__BackingField\":\"MP\",\"&lt;ProductId&gt;k__BackingField\":954364834,\"&lt;ProductSubClass&gt;k__BackingField\":\"MP_SC1\",\"&lt;RateStructureCode&gt;k__BackingField\":\"\",\"&lt;RelatedCustomerValue&gt;k__BackingField\":\"\",\"&lt;RelatedMasterCustomerId&gt;k__BackingField\":null,\"&lt;RelatedSubCustomerId&gt;k__BackingField\":null,\"&lt;RelationName&gt;k__BackingField\":null,\"&lt;RetainSearchResults&gt;k__BackingField\":true,\"&lt;ShowLocationResult&gt;k__BackingField\":true,\"&lt;ShowPrice&gt;k__BackingField\":true,\"&lt;ShowRateCode&gt;k__BackingField\":true,\"&lt;ThresholdText&gt;k__BackingField\":\"Only #TH left!\",\"&lt;WaitlistText&gt;k__BackingField\":\"Waitlisted\",\"&lt;WeekStart&gt;k__BackingField\":\"MONDAY\",\"&lt;WorkFlow&gt;k__BackingField\":\"MP\",\"&lt;WorkflowTabId&gt;k__BackingField\":\"426\"}</CLMPSearchParameters>
      <CLProductClass>MP</CLProductClass>
      <CLGradeInFall>1</CLGradeInFall>
      <CLMPCartItemId>0</CLMPCartItemId>
      <ProductList>
        <CartItemProduct>
          <TotalFreeBadges>0</TotalFreeBadges>
          <TotalPaidBadges>0</TotalPaidBadges>
          <ProductName>" . $package['name'] . "</ProductName>
          <ProductId>" . $package['id'] . "</ProductId>
          <ShipMasterCustomerId>$user_id</ShipMasterCustomerId>
          <ShipSubCustomerId>0</ShipSubCustomerId>
          <Quantity>1</Quantity>
          <Subsystem>PCK</Subsystem>
          <Price xsi:nil=\"true\" />
          <UmbrellaProductId xsi:nil=\"true\" />
          <QualifiedRateStructures>Regular</QualifiedRateStructures>
          <AlwaysRenewFlag xsi:nil=\"true\" />
          <OptedOutFlag xsi:nil=\"true\" />
          <MembershipStartDate xsi:nil=\"true\" />
          <MembershipEndDate xsi:nil=\"true\" />
          <UniqueID xsi:nil=\"true\" />
          <ShipLabelName>$user_id</ShipLabelName>
          <OrderLineNumber xsi:nil=\"true\" />
          <CLSessionCount>0</CLSessionCount>
          <CLStartDate>0001-01-01T00:00:00</CLStartDate>
          <CLPrice xsi:nil=\"true\" />
          <CLMasterProductId xsi:nil=\"true\" />
          <CLProgramStartDate xsi:nil=\"true\" />
          <CLProgramEndDate xsi:nil=\"true\" />
          <CLProrateComponent xsi:nil=\"true\" />
          <CLDeletePackageComponent>true</CLDeletePackageComponent>
          <CLPrerequisiteCompFlag xsi:nil=\"true\" />
          <SubProducts>";
    foreach ($product_ids as $product_id) {
      $options['body'] .= "
        <CartItemSubProducts>
          <TotalFreeBadges>0</TotalFreeBadges>
          <MeetingBeginDate>0001-01-01T00:00:00</MeetingBeginDate>
          <MeetingEndDate>0001-01-01T00:00:00</MeetingEndDate>
          <IsBadgeProduct>false</IsBadgeProduct>
          <ProductId>$product_id</ProductId>
          <RateStructure>Regular</RateStructure>
          <RateCode>STANDARD</RateCode>
          <Quantity xsi:nil=\"true\" />
          <Subsystem>MTG</Subsystem>
          <Price xsi:nil=\"true\" />
          <UmbrellaProductId xsi:nil=\"true\" />
          <AlwaysRenewFlag xsi:nil=\"true\" />
          <OptedOutFlag xsi:nil=\"true\" />
          <DoNotAddToOrder xsi:nil=\"true\" />
          <UniqueID xsi:nil=\"true\" />
          <GuestRegistrantsCount xsi:nil=\"true\" />
          <IsGuestRegistrantSession xsi:nil=\"true\" />
          <IsDailyRateSession xsi:nil=\"true\" />
          <OrderLineNumber xsi:nil=\"true\" />
          <MbrSubBenefitFlag xsi:nil=\"true\" />
          <MaxRegistrantRegistrations xsi:nil=\"true\" />
          <AvailableMaxRegistrantRegistrations xsi:nil=\"true\" />
          <CLPrerequisiteCompFlag xsi:nil=\"true\" />
          <CLSessionCount>0</CLSessionCount>
          <CLSubProductStartDate>0001-01-01T00:00:00</CLSubProductStartDate>
          <CLComponentFlag>false</CLComponentFlag>
          <SessionBadges />
          <CLCCSessionProducts />
          <CLSubProductPrerequisite />
        </CartItemSubProducts>";
    }
    $options['body'] .= "
          </SubProducts>
          <MeetingBadges />
          <CLChildCareSessionProducts />
          <CLCompPrerequisiteProducts />
        </CartItemProduct>
      </ProductList>
    </AddListOfProductsToCartInput>";

    try {
      $endpoint = $this->dataServiceUrl . '/AddListOfProductsToCart';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $cart_items_ids = [];
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
        $listingItems = $xml->WebShoppingCartItems;
        $children = $listingItems->children();
        foreach ($children->AddWebShoppingCartItemOutput as $item) {
          $cart_items_ids[] = (string) $item->WebShoppingCartItemId;
        }
        // Ask additional questions.
        $data['data']['additional'] = $this->askAdditionalQuestions($cart_items_ids);
        // Ask emergency contact info.
        $data['data']['emergency'] = $this->emergencyContactsRetrieveRecords($user_id);
        $data['cart_items_ids'] = $cart_items_ids;
        $data['product_ids'] = $product_ids;
        $this->updateWorkflowInCart($cart_items_ids);
        return $data;
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to get Personify products. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to add products into a cart: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to add products into a cart. Please, examine the logs.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function askAdditionalQuestions($cart_items_ids) {
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];
    $cart_items_ids = implode(',', $cart_items_ids);
    $options['body'] = "<StoredProcedureRequest xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
      <StoredProcedureName>USR_USP_Y_FetchDynamicQuestionsDetails</StoredProcedureName>
      <IsUserDefinedFunction>false</IsUserDefinedFunction>
      <IsUDFScalar xsi:nil=\"true\" />
      <SPParameterList>
        <StoredProcedureParameter>
          <Name>@IP_CART_ID</Name>
          <Value>$cart_items_ids</Value>
          <Direction>1</Direction>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
          <Name>@IP_ORDER_NUMBER</Name>
          <Value />
          <Direction>1</Direction>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
          <Name>@IP_ORDER_LINE_NUMBER</Name>
          <Value>0</Value>
          <Direction>1</Direction>
        </StoredProcedureParameter>
      </SPParameterList>
    </StoredProcedureRequest>";

    try {
      $endpoint = $this->dataServiceUrl . '/GetStoredProcedureDataXML';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        // Normalize response content.
        $contents = str_replace("&lt;", "<", $contents);
        $contents = str_replace("&gt;", ">", $contents);
        $xml = simplexml_load_string($contents);
        $json = json_encode($xml);
        $data = json_decode($json, TRUE);
        if ($data) {
          return $data;
        }
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to update the cart. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to update the cart: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to update the cart. Please, examine the logs.');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateCartInfo($answers, $data, $product_ids) {
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];
    $questions_array = $data['Data']['NewDataSet']['Table2'];
    // Prepare xml for a request with answers.
    $body = '';
    foreach ($data['Data']['NewDataSet']['Table'] as $key => $item) {
      // @todo the mapping move to a config.
      $key = $key == 'ID' ? 'Id' : $key;
      $key = $key == 'MASTER_CUSTOMER_ID' ? 'MasterCustomerId' : $key;
      $key = $key == 'SUB_CUSTOMER' ? 'SubCustomerId' : $key;
      $key = $key == 'ORDER_NO' ? 'OrderNumber' : $key;
      $key = $key == 'ORDER_LINE_NO' ? 'OrderLineNumber' : $key;
      $key = $key == 'CART_ID' ? 'WebShoppingCartId' : $key;
      $key = $key == 'RELATED_CART_ID' ? 'RelatedCartId' : $key;
      $key = $key == 'REGISTRANT_INFO' ? 'RegistrantInfo' : $key;
      $key = $key == 'PRODUCT_ID' ? 'ProductId' : $key;
      $key = $key == 'PRODUCT_INFO' ? 'ProductInfo' : $key;
      $key = $key == 'SUBSYSTEM' ? 'Subsystem' : $key;
      $key = $key == 'PRODUCT_CLASS_CODE' ? 'ProductClassCode' : $key;
      $key = $key == 'PRODUCT_SUBCLASS_CODE' ? 'ProductSubClassCode' : $key;
      // Own suggestion, not checked.
      $key = $key == 'PRODUCT_CLASS_QUESTION_ID' ? 'ProductClassQuestionId' : $key;
      $key = $key == 'SORT_ORDER' ? 'SortOrder' : $key;
      $key = $key == 'COPY_ANSWERS_FLAG' ? 'CopyAnswersFeatureFlag' : $key;
      $key = $key == 'COPY_ANSWERS_FROM_ID' ? 'CopyAnswersFromId' : $key;
      // Own suggestion, not checked.
      $key = $key == 'PRODUCT_ID_ROW_NUMBER' ? 'ProductIdRowNumber' : $key;
      $key = $key == 'DYN_QUES_WEB_EDIT_FLAG' ? 'EditFlag' : $key;

      if ($item == 'Y') {
        $item = 'true';
      }
      if ($item == 'N') {
        $item = 'false';
      }
      if (is_array($item) && empty($item)) {
        $body .= '<' . $key . ' />';
        continue;
      }
      $body .= '<' . $key . '>' . $item . '</' . $key . '>';
    }
    // Add fields which exist in documentaion but not in response.
    $body .= '<ProcessedFlag>true</ProcessedFlag>';

    $body .= "</CLDynamicQuestionCustomerProductInfo>
    </CustomerProductInfo>
    <QuestionAnswerInfo>";
    // Put answers here.
    foreach ($questions_array as $question) {
      $body .= '<CLDynamicQuestionQAInfo>';
      // Get answer for a question.
      $a = isset($answers[$question['CODE']]) ? $answers[$question['CODE']] : '';
      foreach ($question as $key => $item) {
        $key = $key == 'ID' ? 'Id' : $key;
        $key = $key == 'CODE' ? 'Code' : $key;
        $key = $key == 'SORT_ORDER' ? 'SortOrder' : $key;
        $key = $key == 'REGISTRANT_INFO' ? 'RegistrantInfo' : $key;
        // Own suggestion, not checked.
        $key = $key == 'CATEGORY_ROW' ? 'CategoryRow' : $key;
        $key = $key == 'QUESTION_TEXT' ? 'QuestionText' : $key;
        // Own suggestion, not checked.
        $key = $key == 'START_DATE' ? 'StartDate' : $key;
        $key = $key == 'ALLOW_MULTIPLE_ANSWERS_FLAG' ? 'AllowMultipleAnswers' : $key;
        $key = $key == 'ANSWER_REQUIRED_FLAG' ? 'AnswerRequired' : $key;
        $key = $key == 'ANSWER_TYPE_CODE' ? 'AnswerTypeCode' : $key;
        $key = $key == 'USR_Y_MAX_TEXT_LENGTH' ? 'MaxLength' : $key;
        $key = $key == 'NO_EDIT' ? 'EditFlag' : $key;
        $key = $key == 'CATEGORY_INFO_ID' ? 'CategoryId' : $key;
        $key = $key == 'CUSTOMER_PRODUCT_INFO_ID' ? 'CustomerProductInfoId' : $key;
        $key = $key == 'SUBSYSTEM' ? 'Subsystem' : $key;
        $key = $key == 'ANSWER_TEXT' ? 'AnswerText' : $key;
        $key = $key == 'ANSWER_TEXT_II' ? 'AnswerTextII' : $key;
        $key = $key == 'APP_QUESTION_ID' ? 'AppQuestionId' : $key;
        $key = $key == 'APP_QUESTION_ANSWER_ID' ? 'AppQuestionAnswerId' : $key;
        $key = $key == 'ORDER_NO' ? 'OrderNumber' : $key;
        $key = $key == 'ORDER_LINE_NO' ? 'OrderLineNumber' : $key;
        $key = $key == 'DYN_QUES_WEB_EDIT_FLAG' ? 'EditFlag' : $key;

        if ($item == 'Y') {
          $item = 'true';
        }
        if ($item == 'N') {
          $item = 'false';
        }
        if ($key == 'AnswerText') {
          $item = $a;
        }
        if ($key == 'AnswerTextII') {
          $item = 'testAnswer2';
        }
        if (is_array($item) && empty($item)) {
          $body .= '<' . $key . ' />';
          continue;
        }
        $body .= '<' . $key . '>' . (string) $item . '</' . $key . '>';
      }
      // Add fields which exist in documentation but not in response.
      $body .= "<ProcessedFlag>true</ProcessedFlag>
        <CategoryCode />
        <DefaultAnswerSubcode />
        <AnswerValidationCode />
        <MaxLength>0</MaxLength>
        <ReadOnly>false</ReadOnly>
        <CartWebShoppingCartId>0</CartWebShoppingCartId>
        <CartUsrYQuestionId>0</CartUsrYQuestionId>
        <CartComponentCartId>0</CartComponentCartId>
        <EndDate>0001-01-01T00:00:00</EndDate>";

      $body .= '</CLDynamicQuestionQAInfo>';
    }
    $body .= "</QuestionAnswerInfo>
      <CategoryInfo>
        <CLDynamicQuestionCategoryInfo>
          <Id>1</Id>
          <Code />
          <Description />
          <UserInstructions />
          <CustomerProductInfoId>1</CustomerProductInfoId>
          <SortOrder>1</SortOrder>
          <RequiredFlag>true</RequiredFlag>
        </CLDynamicQuestionCategoryInfo>
      </CategoryInfo>
    </CLDynamicQuestionCompleteInfo>";

    $options['body'] = "<StoredProcedureRequest xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
      <StoredProcedureName>USR_USP_Y_UpdateDynamicQuestionsDetails</StoredProcedureName>
      <IsUserDefinedFunction>false</IsUserDefinedFunction>
      <IsUDFScalar xsi:nil=\"true\" />
      <SPParameterList>
        <StoredProcedureParameter>
          <Name>@IP_XML_DATA</Name>
          <Value>";
    $body = "<?xml version=\"1.0\" encoding=\"utf-16\"?>
    <CLDynamicQuestionCompleteInfo xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
      <QuestionMode>Cart</QuestionMode>
      <CustomerProductInfo>
        <CLDynamicQuestionCustomerProductInfo>" . $body;
    $options['body'] .= htmlentities($body);
    $options['body'] .= "</Value>
        <Direction>1</Direction>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
          <Name>@IP_Mode</Name>
          <Value>Cart</Value>
          <Direction>1</Direction>
        </StoredProcedureParameter>
        <StoredProcedureParameter>
          <Name>@IP_WORKFLOW_COMPLETED</Name>
          <Value>Y</Value>
          <Direction>1</Direction>
        </StoredProcedureParameter>
      </SPParameterList>
    </StoredProcedureRequest>";

    try {
      $endpoint = $this->dataServiceUrl . '/GetStoredProcedureDataXML';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
        if ($xml->Data == '<NewDataSet />') {
          $products = $this->checkProductAvailability($product_ids);
          $this->confirmation($answers['cart_items_ids'], $products, $data);
        }
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to update the cart. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to update the cart: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to update the cart. Please, examine the logs.');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkProductAvailability($product_ids) {
    $products = [];
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];
    if (!is_array($product_ids)) {
      $product_ids = explode(',', $product_ids);
    }
    $options['body'] = "<CL_GetProductListingInput><Products>";
    foreach ($product_ids as $product_id) {
      $options['body'] .= "<CL_ProductInfoInput><ProductId>$product_id</ProductId></CL_ProductInfoInput>";
    }
    $options['body'] .= "</Products></CL_GetProductListingInput>";

    try {
      $options['timeout'] = 90;
      $endpoint = $this->dataServiceUrl . '/CL_GetProductListing';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
        $listingItems = $xml->ProductListingRecord;
        $children = $listingItems->children();
        foreach ($children->CL_ProductListingRecord as $product) {
          $products[(string) $product->ProductId] = [
            'available' => TRUE,
            'info' => $product,
          ];
          if ($product->Capacity - $product->Registrations == 0) {
            // Set product unavailable flag.
            $products[(string) $product->ProductId] = [
              'available' => FALSE,
              'info' => $product,
            ];
            // @todo update related entity in drupal DB.
          }
        }
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to get Personify products. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to get Personify products. Please, examine the logs.');
    }
    return $products;
  }

  /**
   * {@inheritdoc}
   */
  public function confirmation($cart_items_ids, $products, $data) {
    // Redirect on confirmation page after success and check availability of selected dates for a cabin.
    $cabin_available = TRUE;
    $cost = 0;
    $dates = [];
    foreach ($products as $product) {
      // Collect dates.
      $tz = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default'));
      $date = DrupalDateTime::createFromFormat('Y-m-d', substr((string) $product['info']->BeginDate, 0, 10), $tz);
      $date = render($date->format('F d, Y'));
      $dates[(string) $product['info']->BeginDate] = [
        'date' => $date,
        'available' => TRUE,
      ];
      // If at least one of the products is unavailable, set flag as false.
      if (!$product['available']) {
        $cabin_available = FALSE;
        $dates[(string) $product['info']->BeginDate]['available'] = FALSE;
      }
      // Calculate total price.
      $cost += (float) $product['info']->ListPrice;
    }
    $config = \Drupal::config('ymca_camp_du_nord.settings')->getRawData();
    // Get village name for the products.
    if (!empty($product['info']->ProductId)) {
      $p_id = $this->entityQuery
        ->get('cdn_prs_product')
        ->condition('field_cdn_prd_id', $product['info']->ProductId)
        ->execute();
      $p_id = reset($p_id);
      if ($p = $this->entityTypeManager->getStorage('cdn_prs_product')->load($p_id)) {
        if (!$p->field_cdn_prd_cabin_id->isEmpty()) {
          $mapping_id = $this->entityQuery
            ->get('mapping')
            ->condition('type', 'cdn_prs_product')
            ->condition('field_cdn_prd_cabin_id', $p->field_cdn_prd_cabin_id->value)
            ->execute();
          $mapping_id = reset($mapping_id);
          if ($mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
            if (!$mapping->field_cdn_prd_village_ref->isEmpty()) {
              $ref = $mapping->field_cdn_prd_village_ref->getValue();
              $page_id = isset($ref[0]['target_id']) ? $ref[0]['target_id'] : FALSE;
              if ($node = $this->entityTypeManager->getStorage('node')->load($page_id)) {
                $village_name = $node->getTitle();
              }
            }
          }
        }
      }
    }
    $parameters = [
      'village_name' => $village_name,
      'cabin_name' => $data['Data']['NewDataSet']['Table']['PRODUCT_INFO'],
      'cabin_available' => $cabin_available,
      'nights' => count($products),
      'dates' => $dates,
      'cost' => $cost,
      'prev_link' => Url::fromUri('internal:/camps/camp_du_nord/search/form')->toString(),
      'next_link' => Url::fromUri($config['url_cart'])->toString(),
    ];
    $redirect_url = Url::fromRoute('ymca_camp_du_nord.confirmation_page', $parameters)->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();
  }

  /**
   * {@inheritdoc}
   */
  public function updateWorkflowInCart($cart_items_ids) {
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];
    $cart_items_ids = implode(',', $cart_items_ids);
    $options['body'] = "<CL_UpdateWorkflowInCartInput>
      <CartCSVId>$cart_items_ids</CartCSVId>
      <WorkflowCompletedFlag>true</WorkflowCompletedFlag>
      </CL_UpdateWorkflowInCartInput>";

    try {
      $endpoint = $this->dataServiceUrl . '/CL_UpdateWorkflowInCart';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
        if ($xml->Success) {
          return TRUE;
        }
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to update the cart. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to update the cart: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to update the cart. Please, examine the logs.');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageId($product_id) {
    $package = [];
    $cdn_product_id = \Drupal::entityQuery('cdn_prs_product')
      ->condition('field_cdn_prd_id', $product_id)
      ->execute();
    $cdn_product_id = reset($cdn_product_id);
    if ($cdn_product = \Drupal::service('entity_type.manager')->getStorage('cdn_prs_product')->load($cdn_product_id)) {
      $cabin_id = !$cdn_product->field_cdn_prd_cabin_id->isEmpty() ? $cdn_product->field_cdn_prd_cabin_id->value : '';
    }
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
    ];
    $today = new DrupalDateTime();
    $today = $today->format('Y-m-d');
    $options['body'] = "<CL_GetProductListingInput>
      <ProductClassCode>RESERVATIONS</ProductClassCode>
      <AvailableToOrdersFlag>true</AvailableToOrdersFlag>
      <EcommerceBeginDate>$today</EcommerceBeginDate>
      <EcommerceEndDate>$today</EcommerceEndDate>
      </CL_GetProductListingInput>";
    try {
      $options['timeout'] = 90;
      $endpoint = $this->dataServiceUrl . '/CL_GetProductListing';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
        $listingItems = $xml->ProductListingRecord;
        $children = $listingItems->children();
        foreach ($children->CL_ProductListingRecord as $product) {
          $product_code = (string) $product->ProductCode;
          if (strstr($product_code, $cabin_id)) {
            $package['id'] = (string) $product->ProductId;
            $package['name'] = (string) $product->ShortName;
          }
        }
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to get Personify products. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to get Personify products. Please, examine the logs.');
    }
    return $package;
  }

  /**
   * {@inheritdoc}
   */
  public function emergencyContactsRetrieveRecords($user_id) {
    $data = [];
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
      'timeout' => 90
    ];
    $options['body'] = "";
    try {
      $endpoint = $this->dataServiceUrl . '/CusCommunicationEmergencyContacts()?$filter=(MasterCustomerId%20eq%20%27' . $user_id . '%27)%20and%20(SubCustomerId%20eq%200)';
      $response = $this->client->request('GET', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        $contents = str_replace('m:', '', $contents);
        $contents = str_replace('d:', '', $contents);
        $xml = simplexml_load_string($contents);
        foreach ($xml->entry as $entry) {
          $data[] = (array) $entry->content->properties;
        }
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to get Emergency contact information. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to get Emergency contact information.');
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function emergencyContactsAddRecord($user_id, $record) {
    $options = [
      'headers' => [
        'Content-Type' => 'text/xml',
      ],
      'auth' => [
        $this->dataServiceUser,
        $this->dataServicePassword,
      ],
      'timeout' => 90
    ];
    $options['body'] = "<CL_CustomerEmergencyContactInput xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
      <MasterCustomerId>$user_id</MasterCustomerId>
      <SubCustomerId>0</SubCustomerId>
      <CustomerCommunicationEmergencyContactId>0</CustomerCommunicationEmergencyContactId>
      <EmergencyContactName>" . $record['name'] . "</EmergencyContactName>
      <EmergencyContactRelationship>" . $record['relationship'] . "</EmergencyContactRelationship>
      <PhoneTypeCodeString>CELL</PhoneTypeCodeString>
      <PhoneNumber>" . $record['phone_number'] . "</PhoneNumber>
      <ChangedDate xsi:nil=\"true\" />
      <CL_UserDefinedPhoneTypeCode1String></CL_UserDefinedPhoneTypeCode1String>
      <CL_UserDefinedPhoneNumber1></CL_UserDefinedPhoneNumber1>
      <CL_UserDefinedPhoneTypeCode2String></CL_UserDefinedPhoneTypeCode2String>
      <CL_UserDefinedPhoneNumber2></CL_UserDefinedPhoneNumber2>
      <CL_UserDefinedYDriverLicense />
      <CL_UserDefinedYAuthorizeToPickupFlag>true</CL_UserDefinedYAuthorizeToPickupFlag>
      <Comments></Comments>
      <Mode>ADD</Mode>
      <Priority>1</Priority>
    </CL_CustomerEmergencyContactInput>";
    try {
      $endpoint = $this->dataServiceUrl . '/CL_CustomerEmergencyContact';
      $response = $this->client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() == '200') {
        $contents = $response->getBody()->getContents();
        $xml = simplexml_load_string($contents);
      }
      else {
        $msg = 'Got %code response from Personify: %msg';
        $this->logger->error(
          $msg,
          [
            '%code' => $response->getStatusCode(),
            '%msg' => $response->getReasonPhrase(),
          ]
        );
        throw new SyncException('Failed to add a new record to Emergency contact information. Please, examine the logs.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to update Personify data: %msg', ['%msg' => $e->getMessage()]);
      throw new SyncException('Failed to add a new record to Emergency contact information.');
    }
  }

}
