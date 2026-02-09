<?php

/**
 * How To — shared content partial.
 *
 * Included by both:
 *   - partials/modal-how-to.php  (inside <dialog>)
 *   - pages/how-to.php           (standalone page)
 *
 * Static HTML only — no DB queries, no PHP variables needed.
 * Markup relies on modal.css styling: <h3> for section titles,
 * <ol> for numbered step lists, <p> for body text.
 */
?>
<section>
  <h3>For Borrowers</h3>
  <p>Finding and borrowing a tool from your neighbors is straightforward.</p>
  <ol>
    <li><strong>Search for a tool.</strong> Browse by category, keyword, or zip code to find what you need in your neighborhood.</li>
    <li><strong>Submit a borrow request.</strong> Choose your dates and send a request to the tool's owner. You'll acknowledge the tool's current condition and accept responsibility for its care.</li>
    <li><strong>Wait for approval.</strong> The lender reviews your request and either approves or denies it. You'll receive a notification either way.</li>
    <li><strong>Pick up the tool.</strong> Once approved, coordinate a pickup. You and the lender each receive a unique handover code&mdash;exchange codes at pickup to confirm the transfer.</li>
    <li><strong>Return when done.</strong> Bring the tool back in the same condition. Exchange handover codes again to confirm the return. Any deposit held is released once the lender verifies the tool's condition.</li>
  </ol>
</section>

<section>
  <h3>For Lenders</h3>
  <p>Share the tools you aren't using and help your community.</p>
  <ol>
    <li><strong>List your tool.</strong> Add photos, a description, and the tool's current condition. Set categories so borrowers can find it easily.</li>
    <li><strong>Set your terms.</strong> Choose whether to require a deposit, set a borrowing fee, and define your availability windows.</li>
    <li><strong>Review requests.</strong> When someone requests your tool, you'll see their profile and reputation. Approve or deny each request at your discretion.</li>
    <li><strong>Confirm pickup.</strong> Exchange handover codes with the borrower at pickup to officially start the loan period.</li>
    <li><strong>Verify the return.</strong> When the tool comes back, inspect its condition and exchange codes to confirm. If everything looks good, the deposit is released automatically.</li>
  </ol>
</section>

<section>
  <h3>Safety &amp; Trust</h3>
  <p>NeighborhoodTools is built to keep every transaction fair and transparent.</p>
  <ol>
    <li><strong>Deposit protection.</strong> Lenders can require a refundable deposit that's held until the tool is returned in good condition. If something goes wrong, deposits can be partially or fully retained.</li>
    <li><strong>Condition documentation.</strong> Each tool's condition is recorded when listed and acknowledged by the borrower before every loan. This creates a clear record if disputes arise.</li>
    <li><strong>Handover codes.</strong> Auto-generated six-character codes confirm both pickup and return, so there's never ambiguity about who has what.</li>
    <li><strong>Ratings &amp; reputation.</strong> After each transaction, both borrower and lender can rate each other. Ratings are visible on profiles, building trust across the community.</li>
    <li><strong>Dispute resolution.</strong> If a problem comes up&mdash;damage, late returns, or disagreements about condition&mdash;either party can open a dispute. Report issues within 48 hours of the return, and an admin will review and resolve it.</li>
  </ol>
</section>