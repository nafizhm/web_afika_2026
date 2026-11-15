<?php

namespace App\Http\Controllers;

use App\Models\{Faq, Property, WhatsappMessage};
use Illuminate\Http\Request;

class AdminTableController extends Controller
{
    public function __invoke(Request $request, string $type)
    {
        $request->validate([
            'draw'=>'nullable|integer|min:0', 'start'=>'nullable|integer|min:0',
            'length'=>'nullable|integer|min:1|max:100', 'search.value'=>'nullable|string|max:500',
            'order.0.column'=>'nullable|integer|min:0|max:10', 'order.0.dir'=>'nullable|in:asc,desc',
        ]);
        [$model, $columns, $searchable] = match ($type) {
            'properties'=>[Property::class, ['name','location','price','is_published','id'], ['name','location','price']],
            'faqs'=>[Faq::class, ['sort_order','question','answer','is_active','id'], ['question','answer','sort_order']],
            'messages'=>[WhatsappMessage::class, ['sender_name','sender_phone','body','message_type','sent_at'], ['sender_name','sender_phone','sender_jid','body','message_type','message_id']],
            default=>abort(404),
        };
        $query=$model::query();
        $total=(clone $query)->count();
        $search=trim((string)$request->input('search.value',''));
        if ($search!=='') $query->where(function ($q) use ($searchable,$search,$type) {
            foreach ($searchable as $column) $q->orWhere($column,'like','%'.$search.'%');
            $label=mb_strtolower($search);
            if ($type==='properties' && in_array($label,['tayang','draft'],true)) $q->orWhere('is_published',$label==='tayang');
            if ($type==='faqs' && in_array($label,['aktif','nonaktif'],true)) $q->orWhere('is_active',$label==='aktif');
        });
        $filtered=(clone $query)->count();
        $index=(int)$request->input('order.0.column', $type==='messages'?4:0);
        $column=$columns[$index]??$columns[0];
        $direction=$request->input('order.0.dir',$type==='messages'?'desc':'asc');
        if ($type==='messages' && $column==='sent_at') $query->orderByRaw('COALESCE(sent_at, created_at) '.$direction);
        else $query->orderBy($column,$direction);
        $rows=$query->orderBy('id','desc')
            ->skip((int)$request->input('start',0))->take((int)$request->input('length',10))->get();
        $data=$rows->map(function ($row) use ($type) {
            return match ($type) {
                'properties'=>['name'=>$row->name,'location'=>$row->location,'price'=>$row->price ? 'Rp '.number_format($row->price,0,',','.') : 'Belum diisi',
                    'status'=>$row->is_published?'Tayang':'Draft','actions'=>view('admin.partials.table-actions',['edit'=>route('admin.property.edit',$row),'delete'=>route('admin.property.destroy',$row),'label'=>'properti'])->render()],
                'faqs'=>['sort_order'=>$row->sort_order,'question'=>$row->question,'answer'=>$row->answer,'status'=>($row->is_active?'Aktif':'Nonaktif').(($row->is_public && $row->faq_type==='general')?' ? Website & AI':' ? Khusus AI'),
                    'actions'=>view('admin.partials.table-actions',['edit'=>route('admin.faq.edit',$row),'delete'=>route('admin.faq.destroy',$row),'label'=>'FAQ'])->render()],
                'messages'=>['sender_name'=>$row->sender_name?:'Tanpa nama','sender_phone'=>$row->sender_phone?:$row->sender_jid,
                    'body'=>$row->body??'[Pesan nonteks]','message_type'=>$row->message_type,
                    'sent_at'=>($row->sent_at??$row->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB'],
            };
        });
        return response()->json(['draw'=>(int)$request->input('draw',0),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$data]);
    }
}
