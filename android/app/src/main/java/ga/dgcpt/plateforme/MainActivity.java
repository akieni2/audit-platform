package ga.dgcpt.plateforme;

import android.app.DownloadManager;
import android.content.Context;
import android.graphics.Color;
import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.URLUtil;
import android.webkit.WebView;
import android.widget.Button;
import android.widget.Toast;

import androidx.coordinatorlayout.widget.CoordinatorLayout;

import com.getcapacitor.BridgeActivity;
import com.getcapacitor.BridgeWebViewClient;

public class MainActivity extends BridgeActivity {
    private static final String PLATFORM_URL = "https://www.dgcpt.ga/dashboard";
    private static final String ZIMBRA_HOST = "mail.tresorpublic.ga";
    private Button returnToDgcptButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Zimbra reste l'unique détenteur du mot de passe. L'application conserve
        // seulement ses cookies de session HTTPS entre deux ouvertures, comme un
        // navigateur mobile, afin d'éviter une nouvelle connexion systématique.
        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        cookieManager.setAcceptThirdPartyCookies(bridge.getWebView(), true);
        bridge.getWebView().getSettings().setDomStorageEnabled(true);
        installReturnToDgcptButton();
        bridge.getWebView().setWebViewClient(new BridgeWebViewClient(bridge) {
            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                updateReturnButton(url);
            }
        });

        String currentUserAgent = bridge.getWebView().getSettings().getUserAgentString();
        if (currentUserAgent == null || !currentUserAgent.contains("DGCPT-Android/")) {
            bridge.getWebView().getSettings().setUserAgentString(
                (currentUserAgent == null ? "" : currentUserAgent) + " DGCPT-Android/1.2"
            );
        }

        bridge.getWebView().setDownloadListener((url, userAgent, contentDisposition, mimeType, contentLength) -> {
            try {
                DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                request.setMimeType(mimeType);
                request.addRequestHeader("User-Agent", userAgent);

                String cookies = CookieManager.getInstance().getCookie(url);
                if (cookies != null && !cookies.isBlank()) {
                    request.addRequestHeader("Cookie", cookies);
                }

                String filename = URLUtil.guessFileName(url, contentDisposition, mimeType);
                request.setTitle(filename);
                request.setDescription(getString(R.string.download_description));
                request.setNotificationVisibility(
                    DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED
                );
                request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, filename);

                DownloadManager manager =
                    (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);
                manager.enqueue(request);
                Toast.makeText(this, R.string.download_started, Toast.LENGTH_SHORT).show();
            } catch (Exception exception) {
                Toast.makeText(this, R.string.download_failed, Toast.LENGTH_LONG).show();
            }
        });
    }

    @Override
    public void onPause() {
        CookieManager.getInstance().flush();
        super.onPause();
    }

    @Override
    public void onStop() {
        CookieManager.getInstance().flush();
        super.onStop();
    }

    private void installReturnToDgcptButton() {
        ViewGroup parent = (ViewGroup) bridge.getWebView().getParent();
        returnToDgcptButton = new Button(this);
        returnToDgcptButton.setText("← Retour DGCPT");
        returnToDgcptButton.setTextColor(Color.WHITE);
        returnToDgcptButton.setTextSize(12);
        returnToDgcptButton.setAllCaps(false);
        returnToDgcptButton.setElevation(dp(8));
        returnToDgcptButton.setPadding(dp(14), 0, dp(14), 0);
        returnToDgcptButton.setVisibility(View.GONE);

        GradientDrawable background = new GradientDrawable();
        background.setColor(Color.rgb(10, 56, 126));
        background.setCornerRadius(dp(22));
        returnToDgcptButton.setBackground(background);
        returnToDgcptButton.setOnClickListener(view -> bridge.getWebView().loadUrl(PLATFORM_URL));

        CoordinatorLayout.LayoutParams params = new CoordinatorLayout.LayoutParams(
            CoordinatorLayout.LayoutParams.WRAP_CONTENT,
            dp(44)
        );
        params.gravity = Gravity.TOP | Gravity.END;
        params.setMargins(dp(12), dp(12), dp(12), 0);
        parent.addView(returnToDgcptButton, params);
    }

    private void updateReturnButton(String url) {
        if (returnToDgcptButton == null) {
            return;
        }

        String host = Uri.parse(url == null ? "" : url).getHost();
        returnToDgcptButton.setVisibility(
            ZIMBRA_HOST.equalsIgnoreCase(host) ? View.VISIBLE : View.GONE
        );
    }

    private int dp(int value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }
}
