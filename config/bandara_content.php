<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bandara public content
|--------------------------------------------------------------------------
|
| These values support the code-managed About, Help, Terms and Privacy
| pages. They are intentionally not stored in the CMS/pages table.
|
*/

return [
    'company' => [
        'brand_name' => 'Bandara',
        'legal_name' => 'Bandara LLP',
        'shopping_domain' => 'bandara.shop',
        'corporate_domain' => 'bandarallp.com',
        'registered_address' => '303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001',
        'support_address' => '402B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001',
        'support_email' => 'support@bandarallp.com',
        'privacy_email' => 'privacy@bandarallp.com',
        'support_phone' => '+91 9823170102',
        'support_hours' => 'Monday to Sunday, 10:00 a.m. to 6:00 p.m. IST',
        'gstin' => '27ABEFB3240N1ZE',
        'fssai' => '21526079001348',
        'grievance' => [
            'name' => 'Parag Parulekar',
            'designation' => 'Chief Executive Officer',
            'email' => 'parag@bandarallp.com',
        ],
    ],

    'delivery' => [
        'b2c_base_km' => 2,
        'b2c_base_fee' => 50,
        'b2c_extra_per_km' => 15,
        'b2b_free_km' => 3,
        'b2b_extra_per_km' => 15,
        'same_day_cutoff' => '1:00 p.m.',
        'intercity_b2b_min_kg' => 20,
    ],

    'rewards' => [
        'silver_min' => 0,
        'silver_max' => 999,
        'gold_min' => 1000,
        // Draft 1 closes the otherwise uncovered ₹4,500–₹4,999 band.
        'gold_max' => 4999,
        'platinum_min' => 5000,
    ],

    'policies' => [
        'version' => '1.0',
        'effective_date' => '27 July 2026',
        'last_updated' => '27 July 2026',
    ],

    'faq_categories' => json_decode(<<<'JSON'
[
  {
    "slug": "orders-and-availability",
    "label": "Orders and availability",
    "items": [
      {
        "question": "Do I need an account to shop with Bandara?",
        "answer": "<p>You may browse Bandara’s products without signing in. An account may be required to complete an order and use account features such as saved addresses, order history, Bandara Credit and approved B2B terms.</p>"
      },
      {
        "question": "When is my order confirmed?",
        "answer": "<p>A B2C order is normally confirmed after successful payment and issuance of an order confirmation by Bandara.</p>\n<p>A B2B order may be confirmed after Bandara verifies stock, pricing, minimum order quantity, delivery feasibility, account status and the customer’s approved payment or credit terms. Depending on those terms, full payment, a partial payment or no immediate payment may be required before confirmation.</p>"
      },
      {
        "question": "Does adding a product to my cart reserve it?",
        "answer": "<p>No. Adding a product to the cart does not by itself guarantee availability. Stock is checked again during checkout and order confirmation.</p>"
      },
      {
        "question": "What happens if a product becomes unavailable after I order it?",
        "answer": "<p>Bandara will contact you where reasonably possible. We may offer a suitable alternative, arrange partial fulfilment with your approval, defer the affected item, or cancel it and refund the amount collected for that item.</p>"
      },
      {
        "question": "Will Bandara substitute a product automatically?",
        "answer": "<p>No. Bandara will not make a material substitution without your consent. Where an alternative is available, we will explain the relevant difference in product, brand, weight, price, ingredients or other important characteristics before you accept it.</p>\n<p>We will not knowingly substitute a product in a way that changes an allergen, dietary classification, animal species or another material characteristic without explicit approval.</p>"
      },
      {
        "question": "Can I modify an order after placing it?",
        "answer": "<p>Contact Bandara Customer Support as soon as possible. A change may be possible before dispatch, depending on stock, preparation status, payment and delivery arrangements.</p>\n<p>B2B modifications are considered case by case and may result in revised quantities, prices, taxes, delivery timing or invoice details.</p>"
      },
      {
        "question": "Can I cancel my order?",
        "answer": "<p>You may cancel an order before it has been dispatched or marked out for delivery. Bandara’s ordinary products are generally maintained in pre-cut or pre-packed saleable form, so cancellation may still be possible after picking or packing has begun.</p>\n<p>Orders normally cannot be cancelled after dispatch.</p>"
      },
      {
        "question": "Can Bandara cancel an order?",
        "answer": "<p>Bandara may cancel all or part of an order where stock is unavailable, the address is not serviceable, payment or credit verification fails, suspected fraud or misuse is identified, fulfilment would be unsafe, or a clear technical or pricing error has occurred. Any amount collected for a cancelled item or order will be refunded.</p>\n<hr />"
      }
    ],
    "description": "Order confirmation, stock, changes and cancellations.",
    "icon": "bag"
  },
  {
    "slug": "delivery",
    "label": "Delivery",
    "items": [
      {
        "question": "Where does Bandara deliver?",
        "answer": "<p>Bandara provides local B2C and B2B delivery within Pune, subject to address serviceability.</p>\n<p>Intercity delivery may be arranged for B2B orders where a suitable courier, refrigerated carrier or cold-chain service is available. Intercity B2B orders are ordinarily considered for shipments of at least 20 kg, unless Bandara agrees otherwise.</p>"
      },
      {
        "question": "How quickly will my order be delivered?",
        "answer": "<p>Orders confirmed by 1:00 p.m. are normally delivered within Pune on the same day, subject to stock, delivery capacity, traffic and serviceability.</p>\n<p>Orders confirmed after 1:00 p.m. are normally delivered on the next delivery day, although Bandara may fulfil them earlier where capacity permits. Delivery operates seven days a week, subject to notified closures and circumstances beyond Bandara’s reasonable control.</p>"
      },
      {
        "question": "Does Bandara offer scheduled delivery slots?",
        "answer": "<p>The current ordering system does not offer customer-selected delivery slots. Bandara will provide or update the expected delivery timing through the order process or customer communication.</p>"
      },
      {
        "question": "How are B2C delivery charges calculated?",
        "answer": "<p>For eligible B2C deliveries, the standard charge is ₹50 for the first 2 kilometres and ₹15 for each additional kilometre. The applicable distance and charge are calculated from the delivery address using Bandara’s designated distance service and are shown before the order is confirmed.</p>"
      },
      {
        "question": "How are local B2B delivery charges calculated?",
        "answer": "<p>Local B2B delivery is free for the first 3 kilometres. A charge of ₹15 applies for each additional kilometre. The applicable charge is displayed at checkout or communicated before order confirmation.</p>"
      },
      {
        "question": "How are intercity B2B delivery charges calculated?",
        "answer": "<p>Intercity B2B delivery is quoted separately for each case. The quotation may take into account destination, shipment weight, packaging, refrigeration or cold-chain requirements, carrier charges, insurance, handling and delivery time.</p>\n<p>An intercity order is not treated as finally confirmed until the customer accepts the applicable quotation and completes any payment or deposit required by Bandara.</p>"
      },
      {
        "question": "Are cold-chain or handling charges applicable?",
        "answer": "<p>Cold-chain, packaging or handling charges may apply where shown during checkout or disclosed before confirmation. These charges depend on the products, destination and fulfilment requirements of the order.</p>"
      },
      {
        "question": "What happens if I am unavailable when the order arrives?",
        "answer": "<p>Please ensure that you or an authorised recipient is available at the delivery address. At your confirmed request, Bandara may hand the order to building security, reception, an employee, a neighbour or another authorised person. Delivery is treated as completed when the order is handed to that person.</p>\n<p>If no authorised recipient is available, Bandara may return the order to its facility. Redelivery depends on product condition, cold-chain integrity, remaining shelf life and delivery capacity, and an additional charge may apply. A perishable order may not qualify for a refund where safe redelivery is no longer possible because the customer or authorised recipient was unavailable.</p>"
      },
      {
        "question": "Can my order be left with security, reception or a neighbour?",
        "answer": "<p>Yes, but only when you have authorised it. Please ensure that the recipient can place chilled or frozen products into appropriate storage immediately.</p>"
      },
      {
        "question": "What happens if my address or phone number is incorrect?",
        "answer": "<p>Customers are responsible for providing a complete address and a reachable phone number. If Bandara identifies an issue before dispatch, we may place the order on hold and contact you by the available details.</p>\n<p>If no reply is received within 48 hours, Bandara may cancel the order and refund the amount collected. If the problem is discovered after dispatch, the order may be treated as an unsuccessful delivery and redelivery may be subject to product safety and additional charges.</p>"
      },
      {
        "question": "What happens if delivery is delayed?",
        "answer": "<p>Delivery may be affected by weather, flooding, traffic restrictions, vehicle problems, public disruption, government action, power failure, transport interruption or another event beyond Bandara’s reasonable control.</p>\n<p>Bandara will provide an updated estimate where reasonably possible. If the order can no longer be delivered safely or the required cold chain cannot be maintained, Bandara may cancel the affected part and refund the amount collected for it.</p>\n<hr />"
      }
    ],
    "description": "Pune delivery, B2B freight, charges and handover.",
    "icon": "truck"
  },
  {
    "slug": "cancellations-replacements-and-refunds",
    "label": "Cancellations, replacements and refunds",
    "items": [
      {
        "question": "Can I return a frozen or perishable product because I changed my mind?",
        "answer": "<p>No. Frozen, chilled and other perishable food products cannot be returned or exchanged merely because you changed your mind, ordered the wrong quantity, no longer require the item or do not personally prefer its taste, texture, appearance or cooking characteristics.</p>"
      },
      {
        "question": "What should I do if I receive the wrong product?",
        "answer": "<p>Contact Bandara Customer Support within one hour of delivery and provide the order number and clear photographs of the product and label. Once verified, Bandara will arrange an appropriate replacement or refund in consultation with you.</p>"
      },
      {
        "question": "What happens if an item is missing from my order?",
        "answer": "<p>Report the missing item within one hour of delivery. Bandara will verify the order and delivery record and, where the claim is confirmed, arrange prompt replacement or refund of the missing item.</p>"
      },
      {
        "question": "What should I do if the packaging is damaged, leaking or unsealed?",
        "answer": "<p>Do not consume the product if its safety or integrity may have been affected. Keep the product, packaging and label under the stated storage conditions and contact Bandara within one hour with photographs or video showing the issue.</p>"
      },
      {
        "question": "What should I do if a frozen product arrives completely thawed?",
        "answer": "<p>Do not consume or refreeze it. Place it in suitable cold storage if it is safe to do so, retain the packaging and contact Bandara within one hour. Bandara will review the delivery conditions and available evidence and arrange an appropriate resolution where the claim is verified.</p>"
      },
      {
        "question": "What if the product appears slightly softened at the surface?",
        "answer": "<p>Minor surface softening, frost loss or a change in visible ice crystals does not by itself determine that a product is unsafe or defective. If you are concerned about its condition, do not consume it and contact Bandara within one hour so the circumstances can be reviewed.</p>"
      },
      {
        "question": "How quickly must I report a problem?",
        "answer": "<p>Visible delivery issues — such as a missing item, incorrect item, damaged packaging, broken seal, leakage or a fully thawed product — should be reported within one hour of delivery.</p>\n<p>If a quality concern was not reasonably visible at handover, contact Bandara as soon as it is discovered. Keep the product, packaging and label available for review.</p>"
      },
      {
        "question": "What evidence should I provide?",
        "answer": "<p>Please provide the order number and clear photographs of the product, outer packaging, seal, product label, batch or lot information, use-by or expiry information and the issue complained of. Bandara may request a short video or other reasonable evidence where necessary.</p>"
      },
      {
        "question": "Must I retain the affected product?",
        "answer": "<p>Yes. Keep the product and its original packaging under the stated storage conditions until Bandara confirms that they may be discarded or arranges collection. A claim may be difficult to verify if the product or label has been discarded.</p>"
      },
      {
        "question": "Will Bandara collect a damaged or disputed product?",
        "answer": "<p>Bandara may arrange collection where inspection or safe disposal is required. We will advise you whether collection is necessary after reviewing the initial information.</p>"
      },
      {
        "question": "Will I receive a replacement, refund or Bandara Credit?",
        "answer": "<p>Bandara may offer a replacement, refund or Bandara Credit in consultation with you. Where a suitable replacement is unavailable or cannot be delivered safely within a reasonable period, a refund will normally be offered. Bandara Credit will be used in place of a monetary refund only with your agreement.</p>"
      },
      {
        "question": "How quickly are approved refunds processed?",
        "answer": "<p>Bandara normally initiates an approved refund within 48 hours. The time required for the amount to appear in your account may depend on the bank, card network, UPI service or payment provider.</p>"
      },
      {
        "question": "Are delivery, cold-chain and handling charges refundable?",
        "answer": "<p>Where the entire order is cancelled or rejected because of an issue attributable to Bandara, the related delivery, cold-chain and handling charges will also be refunded.</p>\n<p>Where only one item in an otherwise correctly delivered order is affected, Bandara will refund or replace the affected item and any charge directly attributable to it. General order-level charges are not automatically refundable in every partial claim.</p>"
      },
      {
        "question": "Can I make a claim after opening the product?",
        "answer": "<p>Opening a product solely to inspect or prepare it does not automatically invalidate a genuine quality complaint. However, Bandara may be unable to verify a claim where the product has been substantially consumed, discarded, altered, improperly stored or retained without its original packaging and label.</p>"
      },
      {
        "question": "Are custom cuts, sliced products, repacked products, cheese and baked goods returnable?",
        "answer": "<p>They are not returnable because of a change of mind. This does not prevent a claim where Bandara supplied the wrong product or a verified delivery, packaging, safety or material quality issue exists.</p>"
      },
      {
        "question": "Can B2B customers return products?",
        "answer": "<p>A B2B return requires Bandara’s prior approval. Approval may be considered for an incorrect product, verified damage, a quality or safety issue, or another commercial exception agreed by Bandara. Approved B2B returns may be resolved through replacement, credit note, account adjustment or refund.</p>\n<hr />"
      }
    ],
    "description": "Perishable-product claims, evidence and refund timing.",
    "icon": "refresh"
  },
  {
    "slug": "products-storage-and-food-handling",
    "label": "Products, storage and food handling",
    "items": [
      {
        "question": "Are product photographs exact?",
        "answer": "<p>Product photographs are representative. Natural products may differ in colour, size, shape, cut, marbling, finish or appearance. These variations do not necessarily indicate a difference in quality.</p>"
      },
      {
        "question": "Are displayed weights exact?",
        "answer": "<p>Fixed-weight packs are supplied at the net quantity declared on the pack. Where a product is sold as an individually selected or recorded catch-weight item, the applicable weight and price are shown on the product, package, order record or invoice.</p>\n<p>Minor surface moisture, glaze or ice loss may occur during handling. Any material discrepancy will be reviewed against the product label, invoiced quantity and Bandara’s dispatch records.</p>"
      },
      {
        "question": "How should frozen products be stored?",
        "answer": "<p>Store frozen products at the temperature stated on the product page and physical label, ordinarily at or below −18°C. Transfer products to appropriate frozen storage promptly after delivery.</p>"
      },
      {
        "question": "How should chilled products be stored?",
        "answer": "<p>Store chilled products at the temperature stated on the product page and physical label, ordinarily between 2°C and 4°C. Do not leave chilled products at room temperature longer than necessary for handling or preparation.</p>"
      },
      {
        "question": "How should I thaw a frozen product?",
        "answer": "<p>Always follow the product-specific instructions on the physical label and Bandara product page. Keep the product sealed during thawing unless the instructions say otherwise. Detailed guidance may differ between meat, seafood, cheese, bakery and ready-to-cook items.</p>\n<p>Do not use standing water or a microwave unless the manufacturer’s instructions specifically permit that method.</p>"
      },
      {
        "question": "Can I refreeze a product after it has thawed?",
        "answer": "<p>Do not refreeze a product after it has completely thawed unless the manufacturer’s label expressly permits it. Refreezing can affect safety, texture and quality.</p>"
      },
      {
        "question": "Where can I find allergen information?",
        "answer": "<p>Ingredients and allergen information are displayed where available from the manufacturer, importer, supplier or packer and should also appear on the applicable physical label.</p>\n<p>Customers with an allergy, intolerance or special dietary requirement should review the delivered label before consumption and contact Bandara where clarification is required.</p>"
      },
      {
        "question": "Can Bandara products contain traces of other allergens?",
        "answer": "<p>Bandara handles a range of products that may include seafood, crustaceans, fish, milk, eggs, cereals containing gluten, soy, nuts and other allergens. A “may contain” statement will be displayed where the relevant supplier, manufacturer, packer or Bandara has identified a cross-contact risk.</p>\n<p>Unless a product is specifically represented as prepared in a dedicated allergen-controlled environment, Bandara cannot guarantee the complete absence of cross-contact.</p>"
      },
      {
        "question": "Which information should I follow if the website and physical label differ?",
        "answer": "<p>Follow the physical label for batch-specific details, ingredients, allergens, storage conditions, preparation instructions, country of origin, manufacturer or importer information and use-by or expiry dates.</p>\n<p>Do not consume the product where a material difference creates uncertainty about safety, allergens or suitability. Contact Bandara for clarification.</p>\n<hr />"
      }
    ],
    "description": "Storage temperatures, thawing, weights and allergens.",
    "icon": "snow"
  },
  {
    "slug": "payments-and-pricing",
    "label": "Payments and pricing",
    "items": [
      {
        "question": "Which payment methods does Bandara accept?",
        "answer": "<p>B2C online payments may be made using the payment methods made available through checkout, including eligible credit cards, debit cards, UPI, net banking and supported wallets.</p>\n<p>Approved B2B customers may also use NEFT, RTGS, IMPS, cheque and approved Pay Later or credit terms. Cash on Delivery is not available.</p>"
      },
      {
        "question": "Does Bandara use Razorpay?",
        "answer": "<p>Yes. Razorpay is used to process eligible online payments. The payment methods actually available may vary by customer, order and payment provider availability.</p>"
      },
      {
        "question": "Does Bandara offer Cash on Delivery?",
        "answer": "<p>No. Cash on Delivery is not currently available for B2C or B2B orders.</p>"
      },
      {
        "question": "What happens if money is debited but my payment is shown as failed?",
        "answer": "<p>Do not immediately make a duplicate payment. Contact Bandara Customer Support with the order or payment reference.</p>\n<p>If Bandara or the payment provider confirms receipt, Bandara will reconcile the payment with the intended order or initiate a refund. If the amount was not received by Bandara, the reversal is handled by the bank or payment provider according to its processing timeline.</p>"
      },
      {
        "question": "What happens if payment succeeds but no order confirmation is created?",
        "answer": "<p>Contact Customer Support with the payment reference. Bandara will verify the transaction and either create or reconcile the intended order where fulfilment is possible, or initiate a refund.</p>"
      },
      {
        "question": "Are B2C prices inclusive of GST?",
        "answer": "<p>Yes. B2C prices are displayed inclusive of applicable GST unless the website clearly states otherwise.</p>"
      },
      {
        "question": "Are B2B prices exclusive of GST?",
        "answer": "<p>Yes. B2B prices are generally displayed exclusive of applicable GST, which is added or shown in the order and invoice as required.</p>"
      },
      {
        "question": "What happens if there is a pricing error?",
        "answer": "<p>Bandara takes reasonable care to display accurate prices, discounts and taxes. If Bandara ordinarily undercharges a customer because of its own error and has accepted the order, Bandara will generally honour the confirmed price.</p>\n<p>If a customer is overcharged, the excess amount will be refunded and any related Bandara Credit adjustment will be made.</p>\n<p>Bandara may correct or cancel an order before dispatch where a price resulted from a clear technical error, an unauthorised or incompatible discount, manipulation or an error that a reasonable customer would recognise as unintended. Any amount collected for a cancelled order will be refunded.</p>"
      },
      {
        "question": "How long does a refund take to appear?",
        "answer": "<p>Bandara normally initiates an approved refund within 48 hours. Your bank, card issuer, UPI service or payment provider may require additional processing time before the refund appears in your account.</p>\n<hr />"
      }
    ],
    "description": "Razorpay, GST, failed payments and pricing corrections.",
    "icon": "card"
  },
  {
    "slug": "b2b-orders",
    "label": "B2B orders",
    "items": [
      {
        "question": "Who can register as a B2B customer?",
        "answer": "<p>Businesses such as hotels, restaurants, caterers, retailers, resellers, institutions and other approved commercial customers may apply for B2B access. Bandara may review and approve the account before B2B pricing or commercial terms become available.</p>"
      },
      {
        "question": "Are B2B prices different from B2C prices?",
        "answer": "<p>Yes. B2B prices, minimum order quantities and product availability may differ from B2C terms and are assigned through Bandara’s administration system.</p>"
      },
      {
        "question": "Do B2B products have minimum order quantities?",
        "answer": "<p>They may. The applicable minimum order quantity is shown for the product or provided as part of the customer’s commercial terms.</p>"
      },
      {
        "question": "Can B2B customers use Pay Later?",
        "answer": "<p>Yes, where Pay Later or a credit period has been approved for that customer by Bandara. Credit limits, due dates and payment terms are controlled through the B2B account.</p>"
      },
      {
        "question": "Are partial B2B payments accepted?",
        "answer": "<p>Yes, where permitted for the relevant account, order or invoice. The required advance amount, remaining balance and due date are shown on the invoice, payment record or separately agreed commercial terms.</p>\n<p>A partial payment does not discharge the full invoice. Bandara may hold procurement, preparation, dispatch or further orders until the required amount has been received.</p>"
      },
      {
        "question": "Which offline B2B payment methods are accepted?",
        "answer": "<p>Bandara may accept NEFT, RTGS, IMPS and cheque, subject to verification or realisation. Cash is not accepted. Available payment methods and instructions are displayed or communicated for the relevant invoice.</p>"
      },
      {
        "question": "What happens if a B2B invoice becomes overdue?",
        "answer": "<p>Bandara may place pending or new orders on hold, reduce or suspend the customer’s credit limit, withdraw Pay Later access or require advance payment. Interest or another late-payment charge will apply only where it was expressly agreed as part of the relevant commercial terms.</p>"
      },
      {
        "question": "How is local B2B delivery charged?",
        "answer": "<p>Delivery is free for the first 3 kilometres and ₹15 for each additional kilometre. The calculated charge is displayed at checkout or confirmed before the order is accepted.</p>"
      },
      {
        "question": "Can Bandara arrange intercity B2B delivery?",
        "answer": "<p>Yes, where a suitable transport or cold-chain service is available. Intercity arrangements are quoted separately for each order and ordinarily require a minimum shipment of 20 kg unless Bandara approves an exception.</p>"
      },
      {
        "question": "Can a confirmed B2B order be changed or cancelled?",
        "answer": "<p>A confirmed B2B order may be changed or cancelled before dispatch only with Bandara’s approval. The change may affect product availability, quantity, price, tax, delivery timing or invoice details. Once dispatched, cancellation is not normally permitted.</p>"
      },
      {
        "question": "Do B2B customers earn or redeem Bandara Credit?",
        "answer": "<p>No. Bandara Credit is a B2C-only programme.</p>\n<hr />"
      }
    ],
    "description": "MOQ, credit, partial payment and intercity supply.",
    "icon": "building"
  },
  {
    "slug": "bandara-credit",
    "label": "Bandara Credit",
    "items": [
      {
        "question": "What is Bandara Credit?",
        "answer": "<p>Bandara Credit is Bandara’s B2C customer rewards balance. Eligible credits may be earned on qualifying purchases and redeemed against future eligible B2C orders in the manner shown at checkout.</p>"
      },
      {
        "question": "Who is eligible for Bandara Credit?",
        "answer": "<p>Eligible registered B2C customers may earn and redeem Bandara Credit. B2B accounts and B2B purchases are excluded.</p>"
      },
      {
        "question": "How is Bandara Credit earned?",
        "answer": "<p>The current programme awards base credit equal to 1% of eligible merchandise spend, subject to the programme rules shown on the website. Delivery charges, handling charges, taxes and other non-merchandise amounts may be excluded from eligible spend.</p>\n<p>A qualifying first order and a qualifying repeat purchase may receive additional promotional credit where the applicable programme conditions are met.</p>"
      },
      {
        "question": "When is earned credit added to my account?",
        "answer": "<p>Earned credit may remain pending until the relevant payment and order-completion conditions have been satisfied. It is posted after the order reaches the qualifying successful status defined by the programme.</p>"
      },
      {
        "question": "What are the Bandara Credit tiers?",
        "answer": "<p>Tiers are based on eligible B2C spend during the applicable rolling assessment period:</p>\n<ul>\n<li><strong>Silver:</strong> ₹0 to ₹999</li>\n<li><strong>Gold:</strong> ₹1,000 to ₹4,999</li>\n<li><strong>Platinum:</strong> ₹5,000 and above</li>\n</ul>\n<p>The customer account will show the current tier and any applicable benefits.</p>"
      },
      {
        "question": "Are birthday credits available?",
        "answer": "<p>Eligible customers who have provided their date of birth may receive a birthday credit according to their current tier and the programme rules active at that time.</p>"
      },
      {
        "question": "Can I exchange Bandara Credit for cash?",
        "answer": "<p>No. Bandara Credit is not legal tender and cannot be withdrawn or exchanged for cash.</p>"
      },
      {
        "question": "Can I transfer Bandara Credit to another customer?",
        "answer": "<p>No. Bandara Credit is personal to the eligible customer account and cannot be sold or transferred.</p>"
      },
      {
        "question": "What happens to Bandara Credit if an order is cancelled or refunded?",
        "answer": "<p>Credit earned from the cancelled or refunded purchase may be reversed. Credit redeemed against that order is restored in proportion to the amount actually refunded, subject to any fraud, misuse or valid account adjustment.</p>"
      },
      {
        "question": "Can multiple promotions be combined?",
        "answer": "<p>Sometimes. Whether promotions, coupons and Bandara Credit can be combined depends on the rules attached to the relevant offer and will be shown during checkout.</p>"
      },
      {
        "question": "Can Bandara change the programme?",
        "answer": "<p>Yes. Bandara may amend, suspend or withdraw Bandara Credit, an offer or a programme benefit. Material changes will be reflected in the current programme terms and, where appropriate, communicated through the website or customer account.</p>\n<hr />"
      }
    ],
    "description": "Eligibility, earning, tiers, redemption and reversals.",
    "icon": "sparkle"
  },
  {
    "slug": "accounts-privacy-and-support",
    "label": "Accounts, privacy and support",
    "items": [
      {
        "question": "What customer information does Bandara collect?",
        "answer": "<p>Depending on how you use the website, Bandara may collect your name, email address, phone number, date of birth, billing and delivery addresses, order and invoice information, payment status or references, account activity and customer-support communications.</p>\n<p>B2B accounts may also contain business details and commercial terms associated with the account.</p>"
      },
      {
        "question": "Why does Bandara ask for my date of birth?",
        "answer": "<p>Bandara collects date of birth for eligible birthday-related Bandara Credit or account benefits and for reasonable account validation. It is not used for general marketing. The Privacy Policy explains how it is used and retained.</p>"
      },
      {
        "question": "Does Bandara track my location?",
        "answer": "<p>Bandara may convert the delivery address you enter into geographic coordinates or distance information to verify serviceability, calculate delivery charges and assist delivery. This is different from continuously tracking your device location.</p>"
      },
      {
        "question": "Can delivery personnel see my phone number and address?",
        "answer": "<p>Assigned delivery personnel may receive the information reasonably necessary to complete the delivery, including the recipient’s name, address, phone number and relevant delivery instructions.</p>"
      },
      {
        "question": "Does Bandara send marketing messages?",
        "answer": "<p>Bandara does not currently send general marketing messages through email, SMS or WhatsApp. Service-related communications concerning account verification, payment, orders, delivery, refunds, support and security may still be sent where necessary.</p>"
      },
      {
        "question": "Which service providers does Bandara use?",
        "answer": "<p>Bandara currently uses service providers that may include Razorpay for eligible online payments, Google services for maps or distance, translation and reCAPTCHA, and IBM for hosting or backup. The complete Privacy Policy will explain the purpose of these services and the information involved.</p>"
      },
      {
        "question": "How can I correct my information or request account deletion?",
        "answer": "<p>Submit a support ticket through your Bandara account or contact Customer Support. Some order, invoice, tax, fraud-prevention or legal records may need to be retained even after an account-deletion request.</p>"
      },
      {
        "question": "How can I contact Bandara Customer Support?",
        "answer": "<p><strong>Email:</strong> support@bandarallp.com<br />\n<strong>Phone/WhatsApp:</strong> +91 9823170102<br />\n<strong>Hours:</strong> Monday to Sunday, 10:00 a.m. to 6:00 p.m. IST<br />\n<strong>Support address:</strong> 402B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001</p>"
      },
      {
        "question": "How do I escalate an unresolved complaint?",
        "answer": "<p>You may escalate an unresolved concern to Bandara’s Grievance Officer:</p>\n<p><strong>Parag Parulekar</strong><br />\nChief Executive Officer<br />\nBandara LLP<br />\n<strong>Email:</strong> parag@bandarallp.com<br />\n<strong>Address:</strong> 402B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001</p>\n<p>Please include your order number, account email or phone number, a clear description of the issue and any relevant supporting documents.</p>"
      },
      {
        "question": "What are Bandara’s legal and regulatory details?",
        "answer": "<p><strong>Legal entity:</strong> Bandara LLP<br />\n<strong>Registered address:</strong> 303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001<br />\n<strong>GSTIN:</strong> 27ABEFB3240N1ZE<br />\n<strong>FSSAI licence:</strong> 21526079001348<br />\n<strong>Shopping website:</strong> bandara.shop<br />\n<strong>Corporate website:</strong> bandarallp.com</p>\n<hr />"
      }
    ],
    "description": "Personal information, support and grievance escalation.",
    "icon": "shield"
  }
]
JSON, true, 512, JSON_THROW_ON_ERROR),
];
