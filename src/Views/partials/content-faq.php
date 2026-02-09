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
    <p>Anyone who lives in the Asheville or Hendersonville, North Carolina area is welcome to create an account. After you register, your account goes through a brief review before it&rsquo;s activated. Once approved, you can borrow tools from and lend tools to neighbors in your community.</p>
  </div>
</details>

<details>
  <summary>Is there a cost to use the platform?</summary>
  <div>
    <p>Creating an account and browsing tools is completely free. Individual lenders may choose to set a small borrowing fee or require a refundable deposit on their tools&mdash;those terms are shown clearly on each tool&rsquo;s listing before you submit a request. There are no subscription fees or hidden charges from NeighborhoodTools itself.</p>
  </div>
</details>

<details>
  <summary>How do deposits work?</summary>
  <div>
    <p>Some lenders require a deposit before lending out a tool. The deposit is held while the tool is in your possession and released automatically once you return it in good condition and the lender confirms the return. If the tool is damaged, lost, or not returned, the lender may retain part or all of the deposit. Deposit amounts are set by the lender and displayed on the tool&rsquo;s listing page.</p>
  </div>
</details>

<details>
  <summary>What happens if a tool is damaged?</summary>
  <div>
    <p>If a tool is returned in worse condition than when it was borrowed, either party can open a dispute within 48 hours of the return. An administrator will review the details&mdash;including the tool&rsquo;s documented condition before and after the loan&mdash;and decide whether the deposit should be partially or fully retained. The condition of every tool is recorded when it&rsquo;s listed and acknowledged by the borrower before each loan, so there&rsquo;s a clear baseline for comparison.</p>
  </div>
</details>

<details>
  <summary>How do I report a problem?</summary>
  <div>
    <p>If something goes wrong during a borrow&mdash;damage, theft, loss, a late return, or a disagreement about condition&mdash;you can file an incident report from your dashboard. Reports should be submitted within 48 hours of the return. Once filed, an administrator reviews the case, and both parties can add messages until the issue is resolved.</p>
  </div>
</details>

<details>
  <summary>Can I cancel a borrow request?</summary>
  <div>
    <p>Yes. If you&rsquo;ve submitted a borrow request and it hasn&rsquo;t been approved yet, you can cancel it from your dashboard at any time. Once a request has been approved and the tool has been picked up, cancellation is no longer possible&mdash;you&rsquo;ll need to return the tool through the normal return process.</p>
  </div>
</details>

<details>
  <summary>How are ratings calculated?</summary>
  <div>
    <p>After each completed borrow, both the lender and the borrower have the opportunity to rate each other. Ratings are on a five-star scale and are averaged across all of a member&rsquo;s transactions. Your overall rating is displayed on your profile and visible to anyone considering a borrow or loan with you. Consistent, fair interactions build your reputation within the community.</p>
  </div>
</details>

<details>
  <summary>How do I delete my account?</summary>
  <div>
    <p>You can request account deletion from your profile settings. Deleted accounts are soft-deleted, meaning your personal data is removed from public view but transaction records are preserved for dispute resolution and audit purposes. If you have any active borrows or unresolved disputes, those must be completed before your account can be deleted.</p>
  </div>
</details>
