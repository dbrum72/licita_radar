<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novas oportunidades — LicitaRadar</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f4f5;font-family:Arial,Helvetica,sans-serif;color:#252d31;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ count($opportunities) }} {{ count($opportunities) === 1 ? 'nova oportunidade' : 'novas oportunidades' }} para o perfil {{ $profile->name }}. Confira os prazos e avalie os editais.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4f5;">
        <tr><td align="center" style="padding:24px 12px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;">
                <tr><td style="padding:28px;background-color:#1e4d55;border-radius:12px 12px 0 0;">
                    <p style="margin:0 0 20px;color:#e7d6b8;font-size:13px;font-weight:bold;letter-spacing:2px;">LICITARADAR</p>
                    <h1 style="margin:0;color:#ffffff;font-size:28px;line-height:1.25;">Novas oportunidades<br>para sua próxima proposta.</h1>
                    <p style="margin:16px 0 0;color:#e7d6b8;font-size:15px;line-height:1.6;">{{ count($opportunities) }} {{ count($opportunities) === 1 ? 'edital disponível' : 'editais disponíveis' }} para sua avaliação.</p>
                </td></tr>
                <tr><td style="padding:24px 28px;background-color:#ffffff;border-left:1px solid #e4e9eb;border-right:1px solid #e4e9eb;">
                    <p style="margin:0 0 6px;color:#59666b;font-size:11px;font-weight:bold;letter-spacing:1px;">PERFIL DE BUSCA</p>
                    <h2 style="margin:0;font-size:18px;line-height:1.5;color:#1e4d55;">{{ $profile->name }}</h2>
                    <p style="margin:12px 0 0;color:#59666b;font-size:14px;line-height:1.6;">Confira o objeto e o prazo. Na aplicação, salve as oportunidades viáveis e descarte as demais.</p>
                </td></tr>
                @foreach ($opportunities as $opportunity)
                <tr><td style="padding:0 28px 24px;background-color:#ffffff;border-left:1px solid #e4e9eb;border-right:1px solid #e4e9eb;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #ced6d9;border-radius:8px;">
                        <tr><td style="padding:20px;">
                            <p style="margin:0 0 10px;color:#59666b;font-size:12px;line-height:1.5;">{{ $opportunity['modality'] }}@if ($opportunity['location']) · {{ $opportunity['location'] }}@endif</p>
                            <h3 style="margin:0 0 12px;font-size:17px;line-height:1.5;color:#252d31;">{{ $opportunity['object'] }}</h3>
                            <p style="margin:0 0 18px;color:#59666b;font-size:13px;line-height:1.6;">{{ $opportunity['agency'] }}</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4f5;">
                                <tr><td style="padding:12px 14px;">
                                    <p style="margin:0 0 4px;color:#59666b;font-size:11px;font-weight:bold;">PRAZO PARA PROPOSTA</p>
                                    <p style="margin:0;color:#1e4d55;font-size:15px;font-weight:bold;line-height:1.5;">{{ $opportunity['deadline'] }}</p>
                                </td></tr>
                                <tr><td style="padding:0 14px 12px;">
                                    <p style="margin:0 0 4px;color:#59666b;font-size:11px;font-weight:bold;">VALOR ESTIMADO</p>
                                    <p style="margin:0;color:#252d31;font-size:15px;line-height:1.5;">{{ $opportunity['value'] }}</p>
                                </td></tr>
                            </table>
                            <p style="margin:18px 0 0;font-size:14px;"><a href="{{ $opportunity['url'] }}" style="color:#1e4d55;font-weight:bold;text-decoration:underline;">Ver edital no PNCP →</a></p>
                        </td></tr>
                    </table>
                </td></tr>
                @endforeach
                <tr><td align="center" style="padding:4px 28px 28px;background-color:#ffffff;border-left:1px solid #e4e9eb;border-right:1px solid #e4e9eb;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td bgcolor="#1e4d55" style="border-radius:6px;"><a href="{{ $frontendUrl }}" style="display:inline-block;padding:15px 24px;border:1px solid #1e4d55;border-radius:6px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;">Abrir LicitaRadar</a></td></tr></table>
                </td></tr>
                <tr><td style="padding:20px 28px;background-color:#e7d6b8;border-radius:0 0 12px 12px;">
                    <p style="margin:0;font-size:13px;line-height:1.6;color:#1e4d55;"><strong>Revise antes de decidir.</strong> A compatibilidade com o acervo é uma triagem preliminar e não comprova habilitação integral. Confira o edital e a validade das evidências.</p>
                </td></tr>
                <tr><td align="center" style="padding:20px 12px;color:#59666b;font-size:11px;line-height:1.6;">Resumo enviado manualmente pela equipe no LicitaRadar.<br>Horários apresentados no fuso {{ config('app.timezone') }}.</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
