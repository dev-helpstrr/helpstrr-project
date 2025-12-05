<div style="max-height:400px; overflow:auto;">
    @foreach ($variables as $group => $subgroups)
        <details class="mb-2">
            <summary style="cursor:pointer;font-weight:bold;">{{ $group }}</summary>
            @foreach ($subgroups as $subgroupName => $vars)
                <div class="ml-4 mb-2">
                    <strong>{{ $subgroupName }}</strong><br/>
                    @foreach ($vars as $var)
                        <button
                            type="button"
                            style="margin:2px;padding:2px 6px;background:#cce5ff;color:#004085;border:none;border-radius:4px;cursor:pointer;"
                            onclick="insertVariable(@js($var))">
                            {{ $var }}
                        </button>
                    @endforeach
                </div>
            @endforeach
        </details>
    @endforeach
</div>

<script>
function insertVariable(variable) {
    // Safe Blade-escaped variable tag
    const variableText = '{{' + variable + '}}';

    // Target Filament RichEditor (Tiptap)
    const editorContent = document.querySelector('#templateBody .ProseMirror');
    if (!editorContent) return;

    const selection = window.getSelection();
    const range = selection.getRangeAt(0);
    const textNode = document.createTextNode(variableText);
    range.insertNode(textNode);
    range.setStartAfter(textNode);
    range.setEndAfter(textNode);
    selection.removeAllRanges();
    selection.addRange(range);

    editorContent.focus();
}
</script>





