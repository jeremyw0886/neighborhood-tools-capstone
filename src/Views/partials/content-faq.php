<?php

/**
 * FAQs — shared content partial.
 *
 * Included by both:
 *   - partials/modal-faq.php  (inside <dialog>)
 *   - pages/faq.php           (standalone page)
 *
 * Static HTML only — no DB queries, no PHP variables needed.
 * Markup relies on modal.css styling: <details>/<summary> accordion,
 * with answer content wrapped in <div> for border + padding.
 */
?>
<details>
  <summary>Who can join NeighborhoodTools?</summary>
  <div>
    <p>Anyone in the Asheville or Hendersonville, North Carolina area is welcome to create an account. You&rsquo;ll need a valid email address and a local ZIP code to sign up. Once registered, you can immediately start browsing tools and listing your own.</p>
  </div>
</details>

<details>
  <summary>Is there a cost to use the platform?</summary>
  <div>
    <p>Creating an account, browsing tools, and bookmarking listings are completely free. Individual lenders may set a rental fee or require a refundable deposit on their tools&mdash;those terms are displayed on each listing before you submit a request. There are no subscription fees or hidden charges from NeighborhoodTools itself.</p>
  </div>
</details>

<details>
  <summary>How do I find a tool?</summary>
  <div>
    <p>Use the search bar on the homepage or the browse page to search by keyword. You can also filter by category, ZIP code, and maximum rental fee. Results show only tools that are currently available&mdash;anything with an active loan or an availability block is automatically excluded.</p>
  </div>
</details>

<details>
  <summary>How does the borrow process work?</summary>
  <div>
    <p>Select a tool, choose a loan duration, and submit a request. The lender is notified and can review your profile and reputation before approving or denying. Once approved, you sign a waiver acknowledging the tool&rsquo;s condition and accepting liability, then coordinate a pickup using handover codes to confirm the transfer. The same code exchange happens at return.</p>
  </div>
</details>

<details>
  <summary>What are handover codes?</summary>
  <div>
    <p>Handover codes are auto-generated six-character codes issued for both pickup and return. Each party receives their own code to share with the other at the time of transfer. Exchanging codes confirms that both sides agree the handoff happened. Codes expire after 24 hours if unused.</p>
  </div>
</details>

<details>
  <summary>How do deposits work?</summary>
  <div>
    <p>Some lenders require a refundable deposit before a loan begins. The deposit is held while the tool is in your possession and released automatically when you return it in good condition and the lender confirms the return. If the tool is damaged, lost, or unreturned, the deposit may be partially or fully retained. Deposit amounts are set by the lender and displayed on the listing page.</p>
  </div>
</details>

<details>
  <summary>What happens if a tool is damaged?</summary>
  <div>
    <p>If a tool is returned in worse condition than when it was borrowed, either party can file an incident report within 48 hours of the return. Incident types include damage, theft, loss, injury, late return, and condition disputes. An administrator reviews the case&mdash;including the tool&rsquo;s documented condition before and after the loan&mdash;and determines whether the deposit should be partially or fully retained.</p>
  </div>
</details>

<details>
  <summary>What is the difference between an incident and a dispute?</summary>
  <div>
    <p>An incident report documents what happened&mdash;damage, a late return, a missing tool, and so on. If the incident can&rsquo;t be resolved between the two parties, either side can escalate it to a formal dispute. Disputes allow both parties and an administrator to exchange messages until a resolution is reached or the case is dismissed.</p>
  </div>
</details>

<details>
  <summary>Can I cancel a borrow request?</summary>
  <div>
    <p>Yes. If your request hasn&rsquo;t been approved yet, you can cancel it from your borrower dashboard. Once a request has been approved and the tool has been picked up, cancellation is no longer possible&mdash;you&rsquo;ll need to return the tool through the normal return process.</p>
  </div>
</details>

<details>
  <summary>Can I extend a loan?</summary>
  <div>
    <p>If you need more time with a tool, you can request an extension through your dashboard. The original due date is preserved in the system&mdash;extensions are tracked separately so there&rsquo;s always a clear record of the original terms and any changes.</p>
  </div>
</details>

<details>
  <summary>How are ratings calculated?</summary>
  <div>
    <p>After each completed borrow, both the lender and the borrower can rate each other on a five-star scale. Ratings are averaged across all of a member&rsquo;s transactions and displayed on their profile. You cannot rate yourself, and ratings are only available for borrows that reached the returned stage. Consistent, fair interactions build your reputation within the community.</p>
  </div>
</details>

<details>
  <summary>What do the tool conditions mean?</summary>
  <div>
    <p>Every tool is assigned a condition when listed: <strong>new</strong> (unused or like-new), <strong>good</strong> (fully functional with minor cosmetic wear), <strong>fair</strong> (functional but shows noticeable wear), or <strong>poor</strong> (works but has significant wear or limitations). Borrowers acknowledge the stated condition through a waiver before each loan.</p>
  </div>
</details>

<details>
  <summary>How do I delete my account?</summary>
  <div>
    <p>Account deletion is handled as a soft delete&mdash;your personal information is removed from public view, but transaction records are preserved for dispute resolution and audit purposes. Any active borrows or unresolved disputes must be completed before your account can be deleted.</p>
  </div>
</details>
