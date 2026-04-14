/**
 * Gridxflex Announcement Bars with CTA - Admin JavaScript
 * v1.0.0
 */

(function ($) {
  "use strict";

  const GradientPicker = {
    build: function (fieldName, savedValue) {
      const isGradient = savedValue && savedValue.startsWith("linear-gradient");

      let startColor = "#f0f0f0";
      let endColor = "#cccccc";
      let angle = 90;

      if (isGradient) {
        const parsed = this.parseGradient(savedValue);
        startColor = parsed.start;
        endColor = parsed.end;
        angle = parsed.angle;
      } else if (savedValue) {
        startColor = savedValue;
      }

      return `
        <div class="gabc-color-field" data-field="${fieldName}">
          <input type="hidden" name="${fieldName}" class="gabc-gradient-value" value="${savedValue || startColor}" />
          <div class="gabc-mode-toggle">
            <button type="button" class="gabc-mode-btn ${!isGradient ? "is-active" : ""}" data-mode="solid">Solid</button>
            <button type="button" class="gabc-mode-btn ${isGradient ? "is-active" : ""}"  data-mode="gradient">Gradient</button>
          </div>
          <div class="gabc-solid-wrap" ${isGradient ? 'style="display:none"' : ""}>
            <input type="text" class="gabc-color-picker gabc-solid-color" value="${!isGradient ? savedValue : startColor}" />
          </div>
          <div class="gabc-gradient-wrap" ${!isGradient ? 'style="display:none"' : ""}>
            <div class="gabc-gradient-stops">
              <div class="gabc-gradient-stop">
                <span>Start</span>
                <input type="text" class="gabc-color-picker gabc-grad-start" value="${startColor}" />
              </div>
              <div class="gabc-gradient-arrow">→</div>
              <div class="gabc-gradient-stop">
                <span>End</span>
                <input type="text" class="gabc-color-picker gabc-grad-end" value="${endColor}" />
              </div>
              <div class="gabc-angle-wrap">
                <span>Angle</span>
                <div class="gabc-angle-input">
                  <input type="number" class="gabc-grad-angle" value="${angle}" min="0" max="360" />
                  <em>deg</em>
                </div>
              </div>
            </div>
            <div class="gabc-gradient-preview"></div>
          </div>
        </div>
      `;
    },

    parseGradient: function (value) {
      const match = value.match(
        /linear-gradient\(\s*(\d+)deg\s*,\s*(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\))\s*,\s*(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\))\s*\)/,
      );
      if (match) {
        return { angle: parseInt(match[1]), start: match[2], end: match[3] };
      }
      return { angle: 90, start: "#f0f0f0", end: "#cccccc" };
    },

    buildGradientString: function ($field) {
      const start = $field.find(".gabc-grad-start").val();
      const end = $field.find(".gabc-grad-end").val();
      const angle = parseInt($field.find(".gabc-grad-angle").val()) || 90;
      return `linear-gradient(${angle}deg, ${start}, ${end})`;
    },

    syncValue: function ($field) {
      const mode = $field.find(".gabc-mode-btn.is-active").data("mode");
      let value;

      if (mode === "gradient") {
        value = this.buildGradientString($field);
        $field.find(".gabc-gradient-preview").css("background", value);
      } else {
        value = $field.find(".gabc-solid-color").val();
      }

      $field.find(".gabc-gradient-value").val(value);
    },
  };

  const AdminSettings = {
    init: function () {
      this.replaceColorFieldsWithGradientPickers();
      this.setupColorPickers();
      this.setupGradientHandlers();
      this.setupEventHandlers();
      // No datetimepicker – using native HTML5 inputs
      this.toggleVisibilityFields();
      this.setupListActions();
    },

    replaceColorFieldsWithGradientPickers: function () {
      const fields = ["bg_color", "button_color"];
      $.each(fields, function (i, fieldName) {
        const $input = $(`[name="${fieldName}"]`);
        if (!$input.length) return;

        const savedValue = $input.val();
        const html = GradientPicker.build(fieldName, savedValue);

        $input.closest("td").html(html);
      });
    },

    setupColorPickers: function () {
      $(".gabc-color-picker").each(function () {
        const $input = $(this);
        const $field = $input.closest(".gabc-color-field");

        $input.wpColorPicker({
          change: function (event, ui) {
            setTimeout(function () {
              $input.val(ui.color.toString());
              if ($field.length) GradientPicker.syncValue($field);
            }, 0);
          },
          clear: function () {
            if ($field.length) GradientPicker.syncValue($field);
          },
        });
      });
    },

    setupGradientHandlers: function () {
      $(document).on("click", ".gabc-mode-btn", function () {
        const $btn = $(this);
        const $field = $btn.closest(".gabc-color-field");
        const mode = $btn.data("mode");

        $field.find(".gabc-mode-btn").removeClass("is-active");
        $btn.addClass("is-active");

        if (mode === "gradient") {
          $field.find(".gabc-solid-wrap").hide();
          $field.find(".gabc-gradient-wrap").show();
        } else {
          $field.find(".gabc-gradient-wrap").hide();
          $field.find(".gabc-solid-wrap").show();
        }

        GradientPicker.syncValue($field);
      });

      $(document).on("input change", ".gabc-grad-angle", function () {
        const $field = $(this).closest(".gabc-color-field");
        GradientPicker.syncValue($field);
      });

      $(".gabc-color-field").each(function () {
        const $field = $(this);
        const mode = $field.find(".gabc-mode-btn.is-active").data("mode");
        if (mode === "gradient") {
          const value = GradientPicker.buildGradientString($field);
          $field.find(".gabc-gradient-preview").css("background", value);
        }
      });
    },

    setupEventHandlers: function () {
      $("#gabc-notice-form").on("submit", (e) => {
        e.preventDefault();
        this.saveNotice();
      });

      $("#gabc_show_location").on("change", () => {
        this.toggleVisibilityFields();
      });
    },

    toggleVisibilityFields: function () {
      const location = $("#gabc_show_location").val();
      $("#gabc_pages_row").toggle(location === "specific_pages");
      $("#gabc_categories_row").toggle(location === "categories");
      $("#gabc_tags_row").toggle(location === "tags");
      $("#gabc_post_types_row").toggle(location === "post_types");
    },

    setupListActions: function () {
      $(document).on("change", ".gabc-toggle-status", function () {
        const $toggle = $(this);
        const id = $toggle.data("id");
        const enabled = $toggle.is(":checked") ? 1 : 0;

        $.ajax({
          url: gabcAdmin.ajaxurl,
          type: "POST",
          data: {
            action: "gabc_toggle_notice",
            nonce: gabcAdmin.nonce,
            id: id,
            enabled: enabled,
          },
          success: (response) => {
            if (response.success) {
              AdminSettings.showNotice(response.data.message, "success");
            } else {
              AdminSettings.showNotice(
                response.data?.message || "Toggle failed",
                "error",
              );
            }
          },
          error: (xhr) => {
            AdminSettings.showNotice(
              "Toggle request failed: " + xhr.status,
              "error",
            );
          },
        });
      });

      $(document).on("click", ".gabc-delete", function () {
        if (!confirm("Are you sure you want to delete this notice?")) return;

        const id = $(this).data("id");
        const $row = $(this).closest("tr");

        $.ajax({
          url: gabcAdmin.ajaxurl,
          type: "POST",
          data: {
            action: "gabc_delete_notice",
            nonce: gabcAdmin.nonce,
            id: id,
          },
          success: (response) => {
            if (response.success) {
              $row.fadeOut(() => $row.remove());
              AdminSettings.showNotice(response.data.message, "success");
            } else {
              AdminSettings.showNotice(
                response.data?.message || "Delete failed",
                "error",
              );
            }
          },
          error: (xhr) => {
            AdminSettings.showNotice(
              "Delete request failed: " + xhr.status,
              "error",
            );
          },
        });
      });

      $(document).on("click", ".gabc-reset-stats", function () {
        if (
          !confirm(
            "Are you sure you want to reset all analytics data for this notice? This cannot be undone.",
          )
        )
          return;

        const id = $(this).data("id");
        const $btn = $(this);

        $.ajax({
          url: gabcAdmin.ajaxurl,
          type: "POST",
          data: {
            action: "gabc_reset_stats",
            nonce: gabcAdmin.nonce,
            id: id,
          },
          success: (response) => {
            if (response.success) {
              // Update the stat badges in the same row (list page) or just show notice.
              const $row = $btn.closest("tr");
              $row.find(".gabc-stat-views").text("0");
              $row.find(".gabc-stat-clicks").text("0");
              $row.find(".gabc-stat-ctr").text("0%");
              AdminSettings.showNotice(response.data.message, "success");
            } else {
              AdminSettings.showNotice(
                response.data?.message || "Reset failed",
                "error",
              );
            }
          },
          error: (xhr) => {
            AdminSettings.showNotice(
              "Reset request failed: " + xhr.status,
              "error",
            );
          },
        });
      });

      $(document).on("click", ".gabc-duplicate", function () {
        const id = $(this).data("id");

        $.ajax({
          url: gabcAdmin.ajaxurl,
          type: "POST",
          data: {
            action: "gabc_duplicate_notice",
            nonce: gabcAdmin.nonce,
            id: id,
          },
          success: (response) => {
            if (response.success) {
              AdminSettings.showNotice(response.data.message, "success");
              setTimeout(() => location.reload(), 1000);
            } else {
              AdminSettings.showNotice(
                response.data?.message || "Duplicate failed",
                "error",
              );
            }
          },
          error: (xhr) => {
            AdminSettings.showNotice(
              "Duplicate request failed: " + xhr.status,
              "error",
            );
          },
        });
      });
    },

    saveNotice: function () {
      const $form = $("#gabc-notice-form");
      const $button = $form.find('button[type="submit"]');
      const origTxt = $button.text();

      $button.prop("disabled", true).text("Saving…");

      const formArray = $form.serializeArray();
      const postData = {
        action: "gabc_save_notice",
        _wpnonce: gabcAdmin.nonce,
      };

      $.each(formArray, function (i, field) {
        if (field.name.includes("[]")) {
          if (!postData[field.name]) postData[field.name] = [];
          postData[field.name].push(field.value);
        } else {
          postData[field.name] = field.value;
        }
      });

      $.ajax({
        url: gabcAdmin.ajaxurl,
        type: "POST",
        data: postData,
        traditional: true,
        success: (response) => {
          if (response.success) {
            this.showNotice(response.data.message, "success");
            if (response.data.redirect) {
              setTimeout(
                () => (window.location.href = response.data.redirect),
                1000,
              );
            }
          } else {
            this.showNotice(
              response.data?.message || "Error saving notice",
              "error",
            );
          }
        },
        error: (xhr) => {
          this.showNotice("An error occurred: " + xhr.status, "error");
        },
        complete: () => {
          $button.prop("disabled", false).text(origTxt);
        },
      });
    },

    showNotice: function (message, type) {
      $(".gabc-admin .notice").remove();

      const $notice = $(`
        <div class="notice notice-${type} is-dismissible">
          <p>${message}</p>
          <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss</span>
          </button>
        </div>
      `);

      $(".gabc-admin h1").after($notice);
      setTimeout(() => $notice.fadeOut(() => $notice.remove()), 3500);
      $notice.on("click", ".notice-dismiss", () =>
        $notice.fadeOut(() => $notice.remove()),
      );
    },
  };

  $(document).ready(() => AdminSettings.init());
})(jQuery);
